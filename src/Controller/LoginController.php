<?php

namespace App\Controller;

use App\Entity\Story;
use App\Entity\User;
use App\Form\AddType;
use App\Form\DocentType;
use App\Form\RegistrationFormType;
use App\Form\UpdateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
    #[Route('/redirect', name: 'redirect')]
    public function redirectAction(Security $security)
    {

        if ($security->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }
        if ($security->isGranted('ROLE_MEMBER')) {
            return $this->redirectToRoute('app_member');
        }


        return $this->redirectToRoute('app_adventure');

    }
    #[Route('/admin', name: 'app_admin')]
    public function admin(EntityManagerInterface $entityManager): Response
    {
        $story=$entityManager->getRepository(Story::class)->findAll();

        return $this->render('login/admin.html.twig', [
            'controller_name' => 'LoginController',
            'story'=>$story,
        ]);
    }

    #[Route('/member', name: 'app_member')]
    public function member(EntityManagerInterface $entityManager): Response
    {
        $story =$entityManager->getRepository(Story::class)->findAll();
        return $this->render('login/member.html.twig', [
            'controller_name' => 'LoginController',
            'story'=>$story,
        ]);
    }



    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');

    }

    #[Route('/update/{id}', name: 'app_update')]
    public function update( Request $request ,EntityManagerInterface $entityManager,int $id): Response
    {
       $story= $entityManager->getRepository(Story::class)->find($id);

        $form=$this->createForm(UpdateType::class,$story);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $story=$form->getData();
            $entityManager->persist($story);
            $entityManager->flush();
            return $this->redirectToRoute('app_member');
        }

        return $this->render('login/update.html.twig', [
            'controller_name' => 'LoginController',
            'form' => $form,

        ]);
    }
    #[Route('/add', name: 'app_add')]
    public function add( Request $request ,EntityManagerInterface $entityManager): Response
    {
        $entityManager->getRepository(Story::class)->findAll();
        $story= new story();
        $form = $this->createForm(AddType::class, $story);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $story= ($form->getData());
            $entityManager->persist($story);
            $entityManager->flush();
            return $this ->redirectToRoute('app_member');
        }
        return $this->render('login/add.html.twig', [
            'controller_name' => 'LoginController',
            'story'=>$story,
            'form'=>$form

        ]);
    }

    #[Route('/del/{id} ' , name:"del")]

    public function del(EntityManagerInterface $em, Request $request ,int $id): Response
    {

        $story=$em->getRepository(Story::class)->find($id);

        $em->remove($story);
        $em->flush();
        return  $this ->redirectToRoute('app_admin');
    }

    #[Route('/docent', name: 'app_docent')]
    public function registerDocent(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(DocentType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setRoles(['ROLE_DOCENT']);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
           return $this->redirectToRoute("app_admin");

            return $security->login($user, 'form_login', 'main');
        }


        return $this->render('login/docent.html.twig', [
            'registrationForm' => $form,
            'user'=>$user
        ]);
    }


}
