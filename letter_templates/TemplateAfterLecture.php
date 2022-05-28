<?php

namespace app\services\online_course\letter_templates;

use app\models\online_courses\CourseLectureDocuments;
use app\models\online_courses\Documents;
use app\models\online_courses\DocumentsType;
use app\models\online_courses\Lectures;
use Yii;
use yii\web\NotFoundHttpException;

class TemplateAfterLecture extends LetterTemplateCourses
{
    //getCourse() // модель Курса (parent)
    //getSendLetterFrom() // почта, с которой отсылается письмо (parent)
    //getSendLetterTo() // почты "клиентов", которым отправляем письмо (parent)

    /**
     * @return array
     * @throws NotFoundHttpException
     *
     *  Возвращает данные для шаблона письма
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
            'course_id' => $this->getCourse()->id,
            'course_name' => $this->getCourse()->courses_name,

            'lecture_link' => $this->getLink(DocumentsType::LECTURE_LINK),
            'answers_link' => $this->getLink(DocumentsType::ANSWERS_LINK),
            'users_manual_link' => $this->getLink(DocumentsType::USERS_MANUAL_LINK),
            'lecture' => $this->getLecture(),
        ];

        return $data;
    }

    /**
     * @return void
     *
     *  Отправка письма
     */
    public function sendLetter()
    {
        $mailer = Yii::$app->mailer->compose();
        $mailer->setFrom($this->getSendLetterFrom());
        $mailer->setTo($this->getSendLetterFrom()); // посылать с почты компании на почту компании
        $mailer->setBcc($this->getSendLetterTo()); // скрытая копия (сюда вставляются все почты КЛИЕНТОВ)
        $mailer->setSubject($this->getSubjectLetter());
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
            $lecture_model = Lectures::findOne(['id' => $this->lecture_id]);
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
     * @return bool|string
     * @throws NotFoundHttpException
     *
     *   "body" письма
     */
    public function getLetterHtmlBody()
    {
        $lecture_link = $this->getLink(DocumentsType::LECTURE_LINK);
        $answers_link = $this->getLink(DocumentsType::ANSWERS_LINK);
        $users_manual_link = $this->getLink(DocumentsType::USERS_MANUAL_LINK);

        if (!empty($lecture_link) && !empty($answers_link) && !empty($users_manual_link)) {
            $lecture_model = $this->getLecture();
            $params = [
                'lecture' => $lecture_model,
                'lecture_link' => $lecture_link,
                'answers_link' => $answers_link,
                'users_manual_link' => $users_manual_link,
            ];

            $template_html = $this->render('template_after_lecture', $params);

            return $template_html;
        }

        throw new NotFoundHttpException('Не найдена "Ссылка для лекции" или "Ссылка для ответов" или "Ссылка для инструкции" в материалах курса!');
    }

    /**
     * @return Lectures|null
     */
    private function getLecture()
    {
        $lecture_model = Lectures::findOne(['id' => $this->lecture_id]);

        return $lecture_model;
    }

    /**
     * @param $doc_type_id
     * @return mixed
     *
     *  Получение ссылки по Курсу и id Лекции
     */
    private function getLink($doc_type_id)
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
     * @param $view
     * @param $params
     * @return string
     *
     *  Подключение готового шаблона в "body" письма
     */
    private function render($view, $params): string
    {
        return Yii::$app->controller->renderPartial('@app/services/online_course/letter_templates/view_templates/' . $view, $params);
    }
}