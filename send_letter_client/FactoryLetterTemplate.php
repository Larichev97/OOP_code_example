<?php

namespace app\services\Mailer\send_letter_client;

use app\models\SendLetterClient;
use yii\web\NotFoundHttpException;

class FactoryLetterTemplate
{
    private $view_templates = [
        'IPL' => 'template_ipl',
        'SC' => 'template_sc',
    ];

    /**
     * @param int $template_type
     * @param array $sendLetterClientsModel
     * @param string $letter_subject
     * @return LetterTemplateAbstract
     * @throws NotFoundHttpException
     */
    public function initLetterTemplate(
        int $template_type,
        array $sendLetterClientsModel,
        string $letter_subject
    ) :LetterTemplateAbstract
    {
        switch ($template_type) {
            case SendLetterClient::TEMPLATE_TYPE_FOR_IPL:
                $template = new LetterTemplateIPL($sendLetterClientsModel,$this->view_templates['IPL'],$letter_subject,SendLetterClient::EMAIL_COMPANY_IPL);
                break;
            case SendLetterClient::TEMPLATE_TYPE_FOR_SC:
                $template = new LetterTemplateSC($sendLetterClientsModel,$this->view_templates['SC'],$letter_subject,SendLetterClient::EMAIL_COMPANY_SC);
                break;
            default:
                throw new NotFoundHttpException('Не правильный тип шаблона компании!');
        }

        return $template;
    }
}