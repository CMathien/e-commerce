<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UsersAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mail, JWTService $jwt): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            //JWT
            $header = [
                'type' => 'JWT',
                'alg' => 'HS256'
            ];
            $payload = [
                'user_id' => $user->getId()
            ];
            $token = $jwt->generate($header, $payload, $this->getparameter('app.jwtsecret'));

            $mail->send(
                "no-reply@s6d.fr",
                $user->getEmail(),
                'Activation de votre compte S6D',
                'register',
                compact('user', 'token')
            );

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('verif/{token}', name: 'verify_user')]
    public function verifyUser(string $token, JWTService $jwt, UsersRepository $usersRepository, EntityManagerInterface $em): Response
    {
        if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))){
            $payload = $jwt->getPayload($token);
            $user = $usersRepository->find($payload['user_id']);
            if($user && !$user->getIsVerified()){
                $user->setIsVerified(true);
                $em->flush();
                $this->addFlash('success', 'Utilisateur vérifié.');
                return $this->redirectToRoute('profile_index');
            }
        }
        $this->addFlash('danger', 'Le token est invalide ou a expiré.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoiverif', name: 'resend_verif')]
    public function resendVerif(JWTService $jwt, SendMailService $mail, UsersRepository $usersRepository): Response
    {
        $user = $this->getUser();

        if(!$user){
            $this->addFlash('danger', 'Vous devez être connecté(e) pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }
        
        /** @phpstan-ignore-next-line */
        if ($user->getIsVerified()){
            $this->addFlash('warning', 'Utilisateur déjà vérifié.');
            return $this->redirectToRoute('profile_index');
        }

        $header = [
            'type' => 'JWT',
            'alg' => 'HS256'
        ];
        $payload = [
            'user_id' => $user->getId() /** @phpstan-ignore-line */
        ];
        $token = $jwt->generate($header, $payload, $this->getparameter('app.jwtsecret'));

        $mail->send(
            "no-reply@s6d.fr",
            $user->getEmail(), /** @phpstan-ignore-line */
            'Activation de votre compte S6D',
            'register',
            compact('user', 'token')
        );

        $this->addFlash('success', 'E-mail de vérification envoyé.');
        return $this->redirectToRoute('profile_index');
    }
}
