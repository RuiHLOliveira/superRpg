<?php

namespace App\Controller;

use App\Entity\User;
use Firebase\JWT\JWT;
use App\Entity\UserAccess;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthController extends AbstractController
{
    private $authExpirationTime =  1 * 60 . ' seconds';
    private $refreshExpirationTime = 24 * 60 * 60 . ' seconds';
    // private $refreshExpirationTime = 60 . ' seconds';

    /**
     * @Route("/auth/register", name="register", methods={"GET","POST", "OPTIONS"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        try {
            $requestData = $request->toArray();
            $password = $requestData['password'];
            $email = $requestData['email'];

            $user = $this->getDoctrine()->getRepository(User::class)
            ->findOneBy(['email'=>$email]);

            if($user !== null) {
                throw new \Exception("Email already taken.", 1);
            }

            $user = new User();
            $user->setPassword($encoder->encodePassword($user, $password));
            $user->setEmail($email);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            return $this->json([
                'message' => 'Successfully registered! Now, please, login!',
                'user' => $user->getEmail()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['message'=>$e->getMessage()],500);
        }
    }

    /**
     * @Route("/auth/login", name="login", methods={"GET","POST", "OPTIONS"})
     */
    public function login(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $requestData = $request->toArray();
        $user = $userRepository->findOneBy([
            'email' => $requestData['email'],
        ]);
        
        if (!$user) {
            return new JsonResponse(['message' => 'Wrong email or inexisting account.'], 400);
        }

        if (!$encoder->isPasswordValid($user, $requestData['password'])) {
            return new JsonResponse(['message' => 'Wrong password.'], 400);
        }

        $payload = [
            "user" => $user->getUsername(),
            "exp"  => (new \DateTime())->modify($this->authExpirationTime)->getTimestamp(),
        ];

        $payloadRefresh = [
            "user" => $user->getUsername(),
            "exp"  => (new \DateTime())->modify($this->refreshExpirationTime)->getTimestamp(),
        ];

        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        $jwtRefresh = JWT::encode($payloadRefresh, $this->getParameter('jwt_secret'), 'HS256');
        
        //salvar o token no banco para poder ver se é valido e invalidar ao gerar um novo
        // ou para o usuario manualmente invalidar se necessário
        $userAccess = new UserAccess();
        $userAccess->setActive(true);
        $userAccess->setHash($jwt);
        $userAccess->setRefreshToken($jwtRefresh);
        $userAccess->setUser($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($userAccess);
        $em->flush();

        return new JsonResponse([
            'message' => 'success!',
            'token' => sprintf('Bearer %s', $jwt),
            'refresh_token' => sprintf('Bearer %s', $jwtRefresh),
        ]);
    }

    /**
     * @Route("/auth/refreshToken", name="refreshToken", methods={"GET","POST", "OPTIONS"})
     */
    public function refreshToken(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $requestData = $request->toArray();
        $refreshToken = $requestData['refresh_token'];
        $refreshToken = str_replace('Bearer ', '', $refreshToken);

        $authToken = $requestData['token'];
        $authToken = str_replace('Bearer ', '', $authToken);

        try {
            $jwt = (array) JWT::decode(
                $refreshToken,
                $this->getParameter('jwt_secret'),
                ['HS256']
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ],JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = $userRepository->findOneBy([
            'email' => $jwt['user'],
        ]);

        if (!$user ) {
            return new JsonResponse(['message' => 'Email or password is wrong.'], 400);
        }

        $userAccess = $this->getDoctrine()->getRepository(UserAccess::class)->findOneBy([
            'user' => $user,
            'hash' => $authToken,
            'refreshToken' => $refreshToken
        ]);

        if (!$userAccess ) {
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getHash() != $authToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getRefreshToken() != $refreshToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }

        $payload = [
            "user" => $user->getUsername(),
            "exp"  => (new \DateTime())->modify($this->authExpirationTime)->getTimestamp(),
        ];

        $payloadRefresh = [
            "user" => $user->getUsername(),
            "exp"  => $jwt['exp'],
        ];

        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        $jwtRefresh = JWT::encode($payloadRefresh, $this->getParameter('jwt_secret'), 'HS256');
        
        $userAccess->setHash($jwt);
        $userAccess->setRefreshToken($jwtRefresh);
        $userAccess->setLastUsageDate();
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($userAccess);
        $em->flush();

        return new JsonResponse([
            'message' => 'success!',
            'token' => sprintf('Bearer %s', $jwt),
            'refresh_token' => sprintf('Bearer %s', $jwtRefresh),
        ],JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/auth/logout", name="logout", methods={"GET","POST", "OPTIONS"})
     */
    public function logout(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $requestData = $request->toArray();
        $refreshToken = $requestData['refresh_token'];
        $refreshToken = str_replace('Bearer ', '', $refreshToken);

        $authToken = $requestData['token'];
        $authToken = str_replace('Bearer ', '', $authToken);

        try {
            $jwt = (array) JWT::decode(
                $refreshToken,
                $this->getParameter('jwt_secret'),
                ['HS256']
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ],JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = $userRepository->findOneBy([
            'email' => $jwt['user'],
        ]);

        if (!$user ) {
            return new JsonResponse(['message' => 'Email or password is wrong.'], 400);
        }

        $userAccess = $this->getDoctrine()->getRepository(UserAccess::class)->findOneBy([
            'user' => $user,
            'hash' => $authToken,
            'refreshToken' => $refreshToken
        ]);

        if (!$userAccess ) {
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getHash() != $authToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getRefreshToken() != $refreshToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }

        $userAccess->setActive(false);
        $userAccess->setLastUsageDate();
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($userAccess);
        $em->flush();

        return new JsonResponse([
            'message' => 'Logged off successfuly!',
        ],JsonResponse::HTTP_OK);
    }
}
