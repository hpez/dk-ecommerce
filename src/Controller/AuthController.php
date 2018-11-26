<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthController extends AbstractController
{
    /**
     * @Route("/login", methods="GET")
     */
    public function index()
    {
        return $this->render('auth/login.html.twig');
    }

    /**
     * @Route("/auth/login", methods="POST")
     */
    public function login(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->findOneBy([
            'email' => $request->get('email')
        ]);
        if ($user && $passwordEncoder->isPasswordValid($user, $request->get('password')))
            return new Response("successful");
        return new Response("unsuccessful");
    }

    /**
     * @Route("/auth/register", methods="POST")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->findOneBy([
            'email' => $request->get('email')
        ]);
        if (!$user) {
            $user = new User();
            $user->setEmail($request->get('email'));
            $user->setPassword($passwordEncoder->encodePassword($user, $request->get('password')));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            return new Response('successful');
        }
        return new Response('unsuccessful');
    }
}