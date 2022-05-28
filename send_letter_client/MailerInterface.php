<?php

namespace app\services\Mailer\send_letter_client;

interface MailerInterface
{
    public function sendLetters();
}