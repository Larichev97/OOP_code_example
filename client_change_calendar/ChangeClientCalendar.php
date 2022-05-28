<?php

namespace app\services\online_course\client_change_calendar;

use app\models\asterisk\OnlineCoursesAutodialler;
use app\models\online_courses\Calendar;
use app\models\online_courses\ClientContacts;
use app\models\online_courses\ClientGroupCalendar;
use app\models\online_courses\ClientChangeCalendar;
use app\models\online_courses\ClientGroups;
use app\models\online_courses\ClientGroupUser;
use Carbon\Carbon;
use Yii;

class ChangeClientCalendar implements ClientChangeCalendarInterface
{
    protected $post; // данные из формы

    public function __construct($post = [])
    {
        $this->post = $post;
    }

    /**
     * @return bool|string
     */
    public function clientChangeCalendar()
    {
        if (!empty($this->post['lecture_id']) && !empty($this->post['group_id'])) {
            $calendar = $this->getNewCalendar();

            $model = $this->getClientChangeCalendar($calendar->id);

            if (!empty($calendar) && empty($model)) {
                $this->saveClientChangeCalendar($calendar->id);

                $current_calendar = $this->getCurrentCalendar();

                if (!empty($current_calendar)) {
                    if (1 == $this->post['old_group_flag']) {
                        $this->saveOnlineCoursesAutodialler($calendar);
                    } else {
                        $this->deleteOldClientGroupUser($current_calendar->id);
                        $this->deleteOldClientGroupCalendar($current_calendar->id);
                        $this->updateOnlineCoursesAutodialler($current_calendar, $calendar);
                    }
                }

                return true;
            }

            return 'Разовая смена группы для данной лекции уже выполнена!';
        }

        return 'Не выбрана лекция или временная группа!';
    }

    /**
     *  Get a schedule for a time group
     *
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getNewCalendar()
    {
        $calendar = Calendar::find()
            ->where(['course_id' => $this->post['course_id']])
            ->andWhere(['group_id' => $this->post['group_id']]) // временная группа
            ->andWhere(['lecture_id' => $this->post['lecture_id']])
            ->one();

        return $calendar;
    }

    /**
     *  Getting the schedule for the current group
     *
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getCurrentCalendar()
    {
        $current_calendar = Calendar::find()
            ->where(['course_id' => $this->post['course_id']])
            ->andWhere(['group_id' => $this->post['current_group_id']])
            ->andWhere(['lecture_id' => $this->post['lecture_id']])
            ->one();

        return $current_calendar;
    }

    /**
     *  Getting an already existing record in a table
     *
     * @param $new_calendar_id
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getClientChangeCalendar($new_calendar_id)
    {
        $model = ClientChangeCalendar::find()
            ->where(['client_groups_id' => $this->post['client_groups_id']])
            ->andWhere(['current_group_id' => $this->post['current_group_id']])
            ->andWhere(['group_id' => $this->post['group_id']])
            ->andWhere(['lecture_id' => $this->post['lecture_id']])
            ->andWhere(['calendar_id' => $new_calendar_id])
            ->one();

        return $model;
    }

    /**
     * @param $calendar_id
     * @return void
     */
    private function saveClientChangeCalendar($calendar_id)
    {
        $model = new ClientChangeCalendar();

        $model->client_groups_id = $this->post['client_groups_id'];
        $model->current_group_id = $this->post['current_group_id'];
        $model->group_id = $this->post['group_id'];
        $model->lecture_id = $this->post['lecture_id'];
        $model->calendar_id = $calendar_id;
        $model->user_add_id = Yii::$app->user->id;
        $model->date_add = Carbon::now()->format('Y-m-d H:i:s');
        $model->old_group_flag = $this->post['old_group_flag'];

        $model->save();
    }

    /**
     * @param $current_calendar_id
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getClientGroupUser($current_calendar_id)
    {
        $client_group_user = ClientGroupUser::find()
            ->where(['client_group_id' => $this->post['client_groups_id']])
            ->andWhere(['calendar_id' => $current_calendar_id])
            ->one();

        return $client_group_user;
    }

    /**
     * For the appointment of technicians
     *
     * @param $current_calendar_id
     * @param $new_calendar_id
     * @return void
     *
     */
    private function updateClientGroupUser($current_calendar_id, $new_calendar_id)
    {
        $client_group_user = $this->getClientGroupUser($current_calendar_id);

        if (!empty($client_group_user)) {
            $client_group_user->calendar_id = $new_calendar_id;  // новое расписание
            $client_group_user->user_id = null; // обнуляется техник для назначения в новой группе
            $client_group_user->save(false);
        }
    }

    /**
     * @param $current_calendar_id
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getClientGroupCalendar($current_calendar_id)
    {
        $client_group_calendar = ClientGroupCalendar::find()
            ->where(['client_group_id' => $this->post['client_groups_id']])
            ->andWhere(['calendar_id' => $current_calendar_id])
            ->one();

        return $client_group_calendar;
    }

    /**
     *  For manual connection
     *
     * @param $current_calendar
     * @param $new_calendar_id
     * @return void
     */
    private function updateClientGroupCalendar($current_calendar_id, $new_calendar_id)
    {
        $client_group_calendar = $this->getClientGroupCalendar($current_calendar_id);

        if (!empty($client_group_calendar)) {
            $client_group_calendar->calendar_id = $new_calendar_id;  // новое расписание
            $client_group_calendar->status_id = null;  // очищается статус прозвона в новой группе
            $client_group_calendar->save(false);
        }
    }

    /**
     * @param $current_calendar
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getOnlineCoursesAutodialler($current_calendar)
    {
        $course_client = $this->getClientGroupsData()->client_id;

        $online_courses_autodialler = OnlineCoursesAutodialler::find()
            ->where(['client_id' => $course_client]) // клиент курса
            ->andWhere(['group_id' => $this->post['current_group_id']])
            ->andWhere(['course_id' => $this->post['course_id']])
            ->andWhere(['calldate' => $current_calendar->date . ' ' . $current_calendar->time])
            ->orderBy(['id' => \SORT_DESC])
            ->one();

        return $online_courses_autodialler;
    }

    /**
     *  For autodialler
     *
     * @param $current_calendar
     * @param $new_calendar
     * @return void
     */
    private function updateOnlineCoursesAutodialler($current_calendar, $new_calendar)
    {
        $online_courses_autodialler = $this->getOnlineCoursesAutodialler($current_calendar);

        if (!empty($online_courses_autodialler)) {
            $online_courses_autodialler->calldate = $new_calendar->date . ' ' . $new_calendar->time;
            $online_courses_autodialler->group_id = $this->post['group_id'];
            $online_courses_autodialler->save(false);
        }
    }

    /**
     * Deleting a row from the old group (so that a technician can be assigned in the new group)
     *
     * @param $current_calendar_id
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function deleteOldClientGroupUser($current_calendar_id)
    {
        $client_group_user = $this->getClientGroupUser($current_calendar_id);

        if (!empty($client_group_user)) {
            $client_group_user->delete();
        }
    }

    /**
     *  Deleting a row from the old calendar (manual connection)
     *
     * @param $current_calendar_id
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function deleteOldClientGroupCalendar($current_calendar_id)
    {
        $client_group_calendar = $this->getClientGroupCalendar($current_calendar_id);

        if (!empty($client_group_calendar)) {
            $client_group_calendar->delete();
        }
    }

    /**
     *  Adding an entry to a `aster_number`.`online_courses_autodialler` table with a temporary group
     *
     * @param $new_calendar
     * @return void
     */
    private function saveOnlineCoursesAutodialler($new_calendar)
    {
        $client_groups_model = $this->getClientGroupsData();

        $client_contact_model = $this->getClientContact($client_groups_model->client_id);

        if (!empty($client_groups_model->number) && \is_numeric($client_groups_model->number)) {
            $client_phone_number = $client_groups_model->number;
        } else {
            $client_phone_number = (\is_numeric($client_contact_model->name)) ? $client_contact_model->name : null;
        }

        $online_courses_autodialler = new OnlineCoursesAutodialler;

        $online_courses_autodialler->client_id = $client_groups_model->client_id;
        $online_courses_autodialler->phone_number = \trim($client_phone_number);
        $online_courses_autodialler->calldate = $new_calendar->date . ' ' . $new_calendar->time;
        $online_courses_autodialler->group_id = $this->post['group_id'];
        $online_courses_autodialler->course_id = $this->post['course_id'];

        if (null !== $client_phone_number) {
            $online_courses_autodialler->save();
        }
    }

    /**
     * @return ClientGroups|null
     */
    private function getClientGroupsData()
    {
        return ClientGroups::findOne(['id' => $this->post['client_groups_id']]);
    }

    /**
     *  Getting the latest entry in the online courses contacts table
     *
     * @param $course_client_id
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getClientContact($course_client_id)
    {
        $client_contact_model = ClientContacts::find()
            ->where(['main' => 1])
            ->orWhere(['type_id' => 1])
            ->andWhere(['client_id' => $course_client_id])
            ->andWhere(['!=', 'name', ''])
            ->orderBy(['id' => \SORT_DESC])
            ->one();

        return $client_contact_model;
    }

}