<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/register", methods="POST")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, RouterInterface $router)
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
            return new RedirectResponse($router->generate('app_product_index'));
        }
        throw new CustomUserMessageAuthenticationException('A user with this email already exist.');
    }
}
