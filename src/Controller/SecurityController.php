<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordType;
use App\User\ResetPasswordHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Controller used to manage the application security.
 * See https://symfony.com/doc/current/security/form_login_setup.html.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class SecurityController extends AbstractController
{
    use TargetPathTrait;

    /*
     * The $user argument type (?User) must be nullable because the login page
     * must be accessible to anonymous visitors too.
     */
    #[Route('/login', name: 'security_login')]
    public function login(
        #[CurrentUser] ?User $user,
        Request $request,
        AuthenticationUtils $helper,
    ): Response {
        // if user is already logged in, don't display the login page again
        if ($user) {
            return $this->redirectToRoute('blog_index');
        }

        // this statement solves an edge-case: if you change the locale in the login
        // page, after a successful login you are redirected to a page in the previous
        // locale. This code regenerates the referrer URL whenever the login page is
        // browsed, to ensure that its locale is always the current one.
        $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('admin_index'));

        return $this->render('security/login.html.twig', [
            // last username entered by the user (if any)
            'last_username' => $helper->getLastUsername(),
            // last authentication error (if any)
            'error' => $helper->getLastAuthenticationError(),
        ]);
    }

    #[Route('/forgot-password', name: 'security_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, ResetPasswordHandler $resetPasswordHandler): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            if ($this->isCsrfTokenValid('forgot_password', $request->request->get('_csrf_token'))) {
                $resetPasswordHandler->sendLoginLink(
                    $request->request->get('_username'),
                );
                $this->addFlash('success', 'security.forgot_password.link_sent');
            } else {
                $this->addFlash('error', 'security.forgot_password.invalid_token');
            }
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/login-link', name: 'security_login_link', methods: ['GET'])]
    public function loginLink(): never
    {
        throw new \LogicException('Check the configuration of "security.firewalls.main.login_link.check_route".');
    }

    #[Route('/reset-password', name: 'security_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        #[CurrentUser]
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response
    {
        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $security->logout(validateCsrfToken: false) ?? $this->redirectToRoute('homepage');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form,
        ]);
    }
}
