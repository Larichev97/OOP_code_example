<?php

namespace app\services\online_course\letter_templates;

use app\models\online_courses\CourseLectureDocuments;
use app\models\online_courses\Documents;
use app\models\online_courses\DocumentsType;
use app\models\online_courses\Lectures;
use Yii;
use yii\web\NotFoundHttpException;

class TemplateBeforeOtherLecture extends LetterTemplateCourses
{
    protected $img_for_body;

    //sendLetter() // отправка письма
    //getCourse() // модель Курса (parent)
    //getSendLetterFrom() // почта, с которой отсылается письмо (parent)
    //getSendLetterTo() // почты "клиентов", которым отправляем письмо (parent)

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

            'users_manual_link' => $this->getLink(DocumentsType::USERS_MANUAL_LINK),
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
        if (!empty($this->lecture_id)) {
            $lecture_model = $this->getLecture();

            if (!empty($lecture_model->lecture_num) && !empty($lecture_model->name)) {
                $text = 'Лекция ' . $lecture_model->lecture_num . ' - ' . $lecture_model->name . '. ';
                $text .= 'Марафон "' . $this->getCourse()->courses_name . '". ';
                $text .= 'Материал для самостоятельного обучения.';

                return $text;
            }

            throw new NotFoundHttpException('Не достаточно данных о Лекции!');
        }

        throw new NotFoundHttpException('Лекция не найдена!');
    }

    /**
     * @return mixed
     * @throws NotFoundHttpException
     *
     *   Получение пути к изображению "Приглашение на лекцию"
     */
    protected function getImgForBodyHtml()
    {
        $img_path = $this->getPathDocForLetterBody(DocumentsType::INVITATION_TO_LECTURE, $this->lecture_id);

        if (!empty($img_path) && \file_exists($img_path)) {
            return $img_path;
        }

        throw new NotFoundHttpException('Не найдено изображение с типом "Приглашение на лекцию" в материалах курса!');
    }

    /**
     * @return string
     * @throws NotFoundHttpException
     *
     *   "body" письма
     */
    public function getLetterHtmlBody()
    {
        $link_users_manual = $this->getLink(DocumentsType::USERS_MANUAL_LINK);

        if (!empty($this->img_for_body) && !empty($link_users_manual)) {
            $body_html = '<p>
                              <h1 style="font-size: 40px;">
                                <a target="_blank" href="' . $link_users_manual . '">Ссылка на инструкцию</a>
                              </h1>
                          </p>';
            $body_html .= '<p><img style="width: 100%;" src="' . $this->img_for_body . '" alt=""></p>';

            return $body_html;
        }

        throw new NotFoundHttpException('Не найдено изображение с типом "Приглашение на лекцию" или "Ссылка для инструкции" в материалах курса');
    }

    /**
     * @param $doc_type_id
     * @return mixed
     *
     *  Получение ссылки по Курсу и id Лекции
     */
    protected function getLink($doc_type_id)
    {
        $course_lec_doc_model = CourseLectureDocuments::find()
            ->alias('cld')
            ->select(['d.link_doc'])
            ->joinWith('document d')
            ->where(['cld.course_id' => $this->getCourse()->id])
            ->andWhere(['cld.lecture_id' => $this->getLecture()->id])
            ->andWhere(['d.way_to_adding_doc' => Documents::ADDING_DOC_LINK])
            ->andWhere(['d.type_id' => $doc_type_id])
            ->asArray()
            ->one();

        return $course_lec_doc_model['link_doc'];
    }

    /**
     * @return Lectures|null
     */
    private function getLecture()
    {
        $lecture_model = Lectures::findOne(['id' => $this->lecture_id]);

        return $lecture_model;
    }
}