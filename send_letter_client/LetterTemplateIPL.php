<?php

namespace app\services\Mailer\send_letter_client;

use app\models\SendLetterClient;
use Carbon\Carbon;
use Yii;

class LetterTemplateIPL extends LetterTemplateAbstract
{
    private $view_params = [];

    /**
     * @return bool
     */
    public function sendLetters()
    {
        foreach ($this->sendLetterClients as $sendLetterClient) {
            $this->useMailer($sendLetterClient['email'], $sendLetterClient['description']);

            $this->updateSendLetterClientModel($sendLetterClient);
        }

        return true;
    }

    /**
     * @param string $client_email
     * @param string $message_to_client
     * @return void
     */
    private function useMailer(string $client_email, string $message_to_client)
    {
        $mailer = Yii::$app->mailer->compose();
        $mailer->setFrom($this->company_email); // from company email
        $mailer->setTo($client_email); // to client's email
        $mailer->setSubject($this->letter_subject);
        $mailer->setHtmlBody($this->getLetterHtmlBody($message_to_client)); // render view template
        $mailer->send();
    }

    /**
     * @param string $message
     * @return string
     */
    public function getLetterHtmlBody(string $message)
    {
        $this->setViewParams($message);

        return $this->renderTemplate($this->getViewParams());
    }

    /**
     * @param SendLetterClient $sendLetterClient
     * @return void
     */
    private function updateSendLetterClientModel(SendLetterClient $sendLetterClient)
    {
        $sendLetterClient->template_type = SendLetterClient::TEMPLATE_TYPE_FOR_IPL;
        $sendLetterClient->date_send = Carbon::now()->format('Y-m-d H:i:s');
        $sendLetterClient->save(false);
    }

    /**
     * @param $message_for_client
     * @return void
     */
    private function setViewParams($message_for_client)
    {
        $this->view_params = [
            'message' => $message_for_client,
        ];
    }

    /**
     * @return array
     */
    private function getViewParams()
    {
        return $this->view_params;
    }


}