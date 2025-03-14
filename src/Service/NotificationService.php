<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private MailerInterface $mailer;

    // Méthode pour envoyer une notification par email
    public function sendNotification(string $email, string $subject, string $message): void
    {
        $emailMessage = (new Email())
            ->from('no-reply@example.com')
            ->to($email)
            ->subject($subject)
            ->html($message);

        $this->mailer->send($emailMessage);
    }

    // Méthode pour envoyer une notification de modification d'historique
    public function sendHistoryUpdateNotification(string $userEmail, string $actionType): void
    {
        $subject = $this->translator->trans('notification.history_update.subject');
        $message = $this->translator->trans('notification.history_update.message', ['actionType' => $actionType]);

        $this->sendNotification($userEmail, $subject, $message);
    }
}