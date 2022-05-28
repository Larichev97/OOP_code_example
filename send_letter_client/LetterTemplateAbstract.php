<?php

namespace app\services\Mailer\send_letter_client;


use Yii;

abstract class LetterTemplateAbstract implements MailerInterface
{
    protected $sendLetterClients;
    protected $render_template;
    protected $letter_subject;
    protected $company_email;

    /**
     * @param array $sendLetterClients
     * @param string $render_template
     * @param string $letter_subject
     * @param string $company_email
     */
    public function __construct(array $sendLetterClients, string $render_template, string $letter_subject , string $company_email)
    {
        $this->sendLetterClients = $sendLetterClients;
        $this->render_template = $render_template;
        $this->letter_subject = $letter_subject;
        $this->company_email = $company_email;
    }

    /**
     * @return mixed
     */
    abstract public function sendLetters();


    /**
     *  Render View template
     *
     * @param array $params
     * @return string
     */
    protected function renderTemplate(array $params)
    {
        return Yii::$app->controller->renderPartial('@app/services/Mailer/send_letter_client/templates/' . $this->render_template, $params);
    }
}