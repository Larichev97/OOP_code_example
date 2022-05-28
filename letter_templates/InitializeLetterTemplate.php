<?php

namespace app\services\online_course\letter_templates;

use app\models\online_courses\Courses;
use app\models\online_courses\LetterTemplateType;

class InitializeLetterTemplate
{
    /**
     *   Returns a specific instance of the class depending on the type of email template
     *
     * @param int $type_letter
     * @param $course_id
     * @param $lecture_id
     * @param $group_id
     * @param $client_email
     * @return LetterTemplateCourses
     */
    public function initTemplate(
        int $type_letter, // Тип письма из дропдауна в модальном окне
        $course_id,
        $lecture_id = null, // если null - тип письма: "для всего курса"
        $group_id = null, // если null - письмо посылается для всех групп в Курсе
        $client_email = null // почта конкретного клиента
    ) :LetterTemplateCourses
    {
        switch ($type_letter) {
            case LetterTemplateType::TEMPLATE_BEFORE_FIRST_LECTURE:
                $template = new TemplateBeforeFirstLecture($this->getCourse($course_id), $client_email, $lecture_id, $group_id);
                break;
            case LetterTemplateType::TEMPLATE_BEFORE_OTHER_LECTURE:
                $template = new TemplateBeforeOtherLecture($this->getCourse($course_id), $client_email, $lecture_id, $group_id);
                break;
            case LetterTemplateType::TEMPLATE_AFTER_LECTURE:
                $template = new TemplateAfterLecture($this->getCourse($course_id), $client_email, $lecture_id, $group_id);
                break;
            case LetterTemplateType::TEMPLATE_AFTER_ENDING_COURSE:
                $template = new TemplateAfterEndingCourse($this->getCourse($course_id), $client_email, $lecture_id, $group_id);
                break;
            case LetterTemplateType::TEMPLATE_AFTER_MEETING:
                $template = new TemplateAfterMeeting($this->getCourse($course_id), $client_email, $lecture_id, $group_id);
                break;
        }

        return $template;
    }

    /**
     *   Getting the course model
     *
     * @param $course_id
     * @return Courses|null
     */
    private function getCourse($course_id)
    {
        $course_model = Courses::findOne(['id' => $course_id]);

        return $course_model;
    }

}