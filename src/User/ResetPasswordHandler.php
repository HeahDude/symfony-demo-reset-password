<?php

namespace App\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class ResetPasswordHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function sendLoginLink(?string $username): void
    {
        if (null === $username) {
            return;
        }

        $user = $this->userRepository->findOneByUsername($username);

        if ($user instanceof User) {
            $this->sendEmail($user);
        }
    }

    private function sendEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from('blog@example.org')
            ->to($user->getEmail())
            ->htmlTemplate('mail/reset_password.html.twig')
            ->context([
                // link is valid for 10 minutes
                'login_link' => $this->loginLinkHandler->createLoginLink($user, null, 600),
            ])
        ;

        $this->mailer->send($email);
    }
}
