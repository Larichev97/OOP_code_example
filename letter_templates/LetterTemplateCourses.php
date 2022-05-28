<?php

namespace app\services\online_course\letter_templates;

use app\models\online_courses\ClientGroups;
use app\models\online_courses\CourseLectureDocuments;
use app\models\online_courses\Courses;
use app\models\online_courses\Documents;
use yii\web\NotFoundHttpException;

abstract class LetterTemplateCourses implements LetterTemplateInterface
{
    protected $course;
    protected $client_email; // почта конкретного клиента
    protected $lecture_id;
    protected $group_id;

    protected $company_email = 'info@smart-comp.net';

    public function __construct(
        Courses $course, // Курс
        string $client_email = null, // почта конкретного клиента
        $lecture_id = null, // если null - тип письма: "для всего курса"
        $group_id = null // если null - письмо посылается для всех групп в курсе
    ) {
        $this->course = $course; // модель курса
        $this->client_email = $client_email; // почта конкретного клиента
        $this->lecture_id = $lecture_id;
        $this->group_id = $group_id;
    }

    /**
     * @return mixed
     *
     *   Возвращает данные для шаблона письма
     */
    abstract public function viewTemplate();

    /**
     * @return mixed
     *
     *   Отправка письма
     */
    abstract public function sendLetter();

    /**
     * @return Courses
     *
     *   Модель Курса
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @return string
     *
     *   Почта, с которой отсылается письмо
     */
    public function getSendLetterFrom()
    {
        return $this->company_email;
    }

    /**
     * @return array|false|string|null
     * @throws NotFoundHttpException
     *
     *   Почты клиентов курса, которым отправляем письмо (или почта конкретного клиента)
     */
    public function getSendLetterTo()
    {
        return $this->setClientEmails();
    }

    /**
     * @return mixed
     *
     *   Тема письма
     */
    abstract public function getSubjectLetter();

    /**
     * @return bool
     *
     *   "body" письма
     */
    public function getLetterHtmlBody()
    {
        return true;
    }

    /**
     * @return void
     *
     *   Файлы для письма
     */
    public function getLetterFiles()
    {

    }

    /**
     * @return array|false|string|null
     *
     * Получение "почты для рассылки" клиента/клиентов
     */
    protected function setClientEmails()
    {
        // Если письмо для конкретного клиента, возвр. только его почту
        if (!empty($this->client_email)) {
            return \trim($this->client_email);
        }

        // Если письмо отправляется всем клиентам [поле mailing_email_contact_id (из ClientGroups)]
        $query = ClientGroups::find()
            ->alias('cg')
            ->select(['cg.mailing_email_contact_id'])
            ->leftJoin('online_courses.group g','cg.group_id=g.id')
            ->leftJoin('online_courses.courses c','g.courses_id=c.id')
            ->where(['c.id' => $this->getCourse()->id]);

        if (!empty($this->group_id)) {
            $query->andWhere(['g.id' => $this->group_id]);
        }

        $clientGroups = $query->asArray()->all();

        $all_clients_emails_in_list = [];

        foreach ($clientGroups as $item) {
            if (!empty($item['mailing_email_contact_id'])) {
                \array_push($all_clients_emails_in_list, $item['mailing_email_contact_id']);
            }
        }

        if (!empty($all_clients_emails_in_list)) {
            $client_emails = \array_unique($all_clients_emails_in_list);

            return $client_emails;
        }

        throw new NotFoundHttpException('Не найдены почты клиентов');
    }

    /**
     * @param int $doc_type_id
     * @param $lectureId
     * @return mixed
     *
     * Получение пути к документу для "body" письма
     */
    protected function getPathDocForLetterBody(int $doc_type_id, $lectureId = null)
    {
        $query = CourseLectureDocuments::find()
            ->alias('cld')
            ->select(['d.file_path'])
            ->joinWith('document d')
            ->where(['cld.course_id' => $this->getCourse()->id]);

        if (null !== $lectureId) {
            $query->andWhere(['cld.lecture_id' => $lectureId]);
        }

        $query
            ->andWhere(['d.way_to_adding_doc' => Documents::ADDING_DOC_UPLOAD])
            ->andWhere(['d.type_id' => $doc_type_id])
            ->orderBy(['d.date_add' => \SORT_DESC]) // брать последний (на всякий случай)
            ->asArray();

        $cld_model = $query->one();

        return $cld_model['file_path'];
    }


    /**
     * @param int $doc_type_id
     * @return mixed
     *
     * Получение ссылки для "body" письма
     */
    protected function getLinkForLetterBody(int $doc_type_id)
    {
        $cld_model = CourseLectureDocuments::find()
            ->alias('cld')
            ->select(['d.link_doc'])
            ->joinWith('document d')
            ->where(['cld.course_id' => $this->getCourse()->id])
            ->andWhere(['d.way_to_adding_doc' => Documents::ADDING_DOC_LINK])
            ->andWhere(['d.type_id' => $doc_type_id])
            ->orderBy(['d.date_add' => \SORT_DESC]) // брать последний (на всякий случай)
            ->asArray()
            ->one();

        return $cld_model['link_doc'];
    }
}