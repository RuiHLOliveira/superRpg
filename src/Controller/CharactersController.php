<?php

namespace App\Controller;

use Exception;
use Throwable;
use App\Entity\Game;
use App\Entity\Character;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CharactersController extends AbstractController
{

    /**
     * @Route("/characters", methods={"GET","HEAD"})
     */
    public function index(Request $request): Response
    {
        try {
            $filters = $request->query->get('filters');
    
            if($filters !== null) {
                $filters = json_decode($filters,true);
                foreach ($filters as $key => $value) {
                    $filters[key($value)] = $value[key($value)];
                    unset($filters[$key]);
                }
            }
    
            $filters['user'] = $this->getUser();
            
            $repository = $this->getDoctrine()->getRepository(Character::class);
            $characters = null;
    
            if($filters == null){
                $characters = $repository->findBy($filters);
            } else {
                $characters = $repository->findBy($filters);
            }
            
            return new JsonResponse(compact('characters'));
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()],500);
        }
    }

    /**
     * @Route("/characters", methods={"POST"})
     */
    public function store(Request $request): Response
    {
        try {
            
            // if($project == null) {
            //     $this->createNotFoundException("Project Not Found");
            // }
            $requestData = $request->toArray();
            if(!isset($requestData['name']) || $requestData['name'] == null || $requestData['name'] == '') {
                throw new ValidationException('Cant create a Character without "Name".');
            }
            if(!isset($requestData['game']) || $requestData['game'] == null || $requestData['game'] == '') {
                throw new ValidationException('Cant create a Character without "Game".');
            }

            $game = $this->getDoctrine()->getRepository(Game::class)->findOneBy([
                'id' => $requestData['game']
            ]);
            if($game == null) {
                $this->createNotFoundException("Game Not Found");
            }

            $character = new Character();
            $character->setName($requestData['name']);
            $character->setGame($game);
            $character->setUser($this->getUser());
            $character->setHp(100);

            $em = $this->getDoctrine()->getManager();
            $em->persist($character);
            $em->flush();
            $character->getId();
            return new JsonResponse(compact('character'));
        } catch (ValidationException $e) {
            return new JsonResponse(['message' => $e->getMessage()],500);
        } catch (Throwable $e) {
            throw $e;
            return new JsonResponse(['message' => 'An error ocurred while creating a character.'],500);
        }
    }
}
