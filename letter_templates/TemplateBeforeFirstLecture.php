<?php

namespace app\services\online_course\letter_templates;

use app\models\online_courses\CourseLectureDocuments;
use app\models\online_courses\Documents;
use app\models\online_courses\DocumentsType;
use Yii;
use yii\web\NotFoundHttpException;

class TemplateBeforeFirstLecture extends TemplateBeforeOtherLecture
{
    //getCourse() // модель Курса (parent)
    //getSendLetterFrom() // почта, с которой отсылается письмо (parent)
    //getSendLetterTo() // почты "клиентов", которым отправляем письмо (parent)
    //getSubjectLetter() // тема письма (parent)
    //getLetterHtmlBody() // "body" письма (parent) от TemplateBeforeOtherLecture.php

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
            'file_paths' => $this->getLetterFiles(),
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

        // Загрузка Расписания группы для письма
        $file = $this->getLetterFiles();
        if (isset($file)) {
            $explode_file = explode('/', $file);
            $mailer->attach($file, ['fileName' => end($explode_file)]);
        }

        $mailer->setHtmlBody($this->getLetterHtmlBody());
        $mailer->send();
    }

    /**
     * @return mixed|void
     * @throws NotFoundHttpException
     */
    public function getLetterFiles()
    {
        if (null !== $this->group_id) {
            $path = $this->getPathTimetableByGroupId($this->group_id);

            if (!empty($path) && \file_exists($path)) {
                return $path;
            }

            throw new NotFoundHttpException('Отсутствует путь к документу с типом "Расписание группы" для "' . \app\models\online_courses\Group::getGroupName($this->group_id) . '" в материалах курса!');
        }

        throw new NotFoundHttpException('Не указана группа!');
    }

    /**
     * @param $group_id
     * @return false|mixed
     *
     *  Получение "расписания группы" по Курсу и id Группы
     */
    private function getPathTimetableByGroupId($group_id)
    {
        $course_lec_doc_model = CourseLectureDocuments::find()
            ->alias('cld')
            ->select(['d.file_path'])
            ->joinWith('document d')
            ->where(['cld.course_id' => $this->getCourse()->id])
            ->andWhere(['d.way_to_adding_doc' => Documents::ADDING_DOC_UPLOAD])
            ->andWhere(['d.type_id' => DocumentsType::GROUP_TIMETABLE])
            ->andWhere(['d.group_id' => $group_id])
            ->asArray()
            ->one();

        if (!empty($course_lec_doc_model['file_path'])) {
            return $course_lec_doc_model['file_path'];
        }

        throw new NotFoundHttpException('Отсутствует путь к документу с типом "Расписание группы" для "' . \app\models\online_courses\Group::getGroupName($this->group_id) . '" в материалах курса!');
    }

}