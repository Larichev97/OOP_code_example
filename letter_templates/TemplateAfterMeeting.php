<?php

namespace app\services\online_course\letter_templates;

use app\models\online_courses\DocumentsType;
use Yii;
use yii\web\NotFoundHttpException;

class TemplateAfterMeeting extends LetterTemplateCourses
{
    private $img_for_body;

    //getCourse() // модель Курса (parent)
    //getSendLetterFrom() // почта, с которой отсылается письмо (parent)
    //getSendLetterTo() // почты "клиентов", которым отправляем письмо (parent)
    //getLetterFiles() // файлы для письма (parent) в этом шаблоне не будет прикрепл. файлов

    /**
     * @return array
     * @throws NotFoundHttpException
     *
     *   Возвращает данные для шаблона письма
     */
    public function viewTemplate()
    {
        $mails_count = 0;

        $arr_client_emails = $this->getSendLetterTo();

        if (\is_string($arr_client_emails)) {
            $mails_count = 1;
        } elseif (!empty($arr_client_emails)) {
            $mails_count = \count($arr_client_emails);
        }

        $data = [
            'send_from' => $this->getSendLetterFrom(),
            'send_to' => $mails_count,
            'subject' => $this->getSubjectLetter(),
            'img_body' => $this->getImgForBodyHtml(),
            'course_id' => $this->getCourse()->id,
            'course_name' => $this->getCourse()->courses_name,

            'link_on_video' => $this->getLinkForLetterBody(DocumentsType::MEETING_LINK),
        ];

        return $data;
    }

    /**
     * @return void
     *
     *   Отправка письма
     */
    public function sendLetter()
    {
        $mailer = Yii::$app->mailer->compose();
        $mailer->setFrom($this->getSendLetterFrom());
        $mailer->setTo($this->getSendLetterFrom()); // посылать с почты компании на почту компании
        $mailer->setBcc($this->getSendLetterTo()); // скрытая копия (сюда вставляются все почты КЛИЕНТОВ)
        $mailer->setSubject($this->getSubjectLetter());

        $this->img_for_body = $mailer->embed($this->getImgForBodyHtml());

        // Загрузка файлов для письма
        $files = $this->getLetterFiles();
        if (isset($files)) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    $explode_file = explode('/', $file);
                    $mailer->attach($file, ['fileName' => end($explode_file)]);
                }
            } else {
                $explode_file = explode('/', $files);
                $mailer->attach($files, ['fileName' => end($explode_file)]); // если только 1 файл
            }
        }

        $mailer->setHtmlBody($this->getLetterHtmlBody());
        $mailer->send();
    }

    /**
     * @return string
     * @throws NotFoundHttpException
     *
     *   Тема письма
     */
    public function getSubjectLetter()
    {
        $course_model = $this->getCourse();
        if (!empty($course_model->courses_name)) {
            $text = 'Встреча выпускников "' . $course_model->courses_name . '"';

            return $text;
        }

        throw new NotFoundHttpException('Не указано название курса!');
    }

    /**
     * @return mixed
     * @throws NotFoundHttpException
     *
     *   Получение пути к изображению "Письмо для встречи"
     */
    private function getImgForBodyHtml()
    {
        $img_path = $this->getPathDocForLetterBody(DocumentsType::LETTER_FOR_MEETING);

        if (!empty($img_path) && \file_exists($img_path)) {
            return $img_path;
        }

        throw new NotFoundHttpException('Не найдено изображение с типом "Письмо для встречи" в материалах курса!');
    }

    /**
     * @return string
     * @throws NotFoundHttpException
     *
     *   "body" письма
     */
    public function getLetterHtmlBody()
    {
        $link_on_video = $this->getLinkForLetterBody(DocumentsType::MEETING_LINK);

        if (!empty($this->img_for_body) && !(empty($link_on_video))) {
            $body_html = '<p>
                              <h1 style="font-size: 40px;">
                                <a target="_blank" href="' . $link_on_video . '">Ссылка на видеозапись</a>
                              </h1>
                          </p>';
            $body_html .= '<p><img style="width: 100%;" src="' . $this->img_for_body . '" alt=""></p>';

            return $body_html;
        }

        throw new NotFoundHttpException('Не найдено изображение с типом "Письмо для встречи" или "Ссылка для встречи" в материалах курса!');
    }

}