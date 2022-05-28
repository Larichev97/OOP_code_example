<?php

namespace app\controllers\tech_support;

use app\controllers\AppController;
use app\models\App;
use app\models\tech_support\ClientContractPaymentPeriod;
use app\models\tech_support\ClientPayment;
use app\models\tech_support\ClientPaymentNotPaid;
use app\models\tech_support\ClientPaymentNotPaidSearch;
use app\models\tech_support\ClientPaymentStatus;
use app\models\tech_support\ClientPeriod;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;

class ClientPaymentNotPaidController extends AppController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['untie-payment'],
                        'allow' => true,
                        'roles' => ['UntiePaymentNotPaidTechSupport'],
                    ],
                ],
            ],
        ];
    }

    /**
     *  Unlinks the payment from the period
     *
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionUntiePayment()
    {
        $post = Yii::$app->request->post();

        $model_not_paid = ClientPaymentNotPaid::findOne($post['not_paid_id']);

        $not_paid = new ClientPaymentNotPaid();

        $card_id = null;

        $card = $not_paid->getPaymentCard($model_not_paid['client_id'], $model_not_paid['number']);

        if (!empty($card)) {
            $card_id = $card->id;
        }

        if (!empty($card_id)) {
            $model_client_payment = $this->getCurrentPaymentModel($model_not_paid, $card_id);

            if (!empty($model_client_payment)) {
                $client_id_ts = $model_not_paid['client_id'] - App::TECH_SUPPORT_900000;

                $model_ccpp = $this->getCurrentCCPPModel($model_client_payment['id'], $client_id_ts);

                $period_ids = $this->getPeriodIdsArray($model_ccpp);

                $this->updateClientPeriods($period_ids, $model_not_paid['amount']);

                $this->updateNotPaid($model_not_paid);

                $this->deleteCCPP($model_ccpp);

                $this->deleteOldClientPayment($model_client_payment);

                return true;
            }
        }

        return false;
    }

    

    /**
     *  Update the paid period by subtracting the amount of the payment that we untie
     *
     * @param array $period_ids
     * @param $amount
     * @return void
     */
    private function updateClientPeriods(array $period_ids, $amount)
    {
        if (!empty($period_ids)) {
            $model_client_periods = ClientPeriod::find()
                ->where(['IN', 'id', $period_ids])
                ->all();

            if (!empty($model_client_periods)) {
                foreach ($model_client_periods as $model_client_period) {
                    $old_pay_debt = \intval($model_client_period->pay_debt);

                    if ($old_pay_debt >= \intval($amount)) {
                        $new_pay_debt = $old_pay_debt - \intval($amount);

                        $model_client_period->pay_debt = $new_pay_debt;
                        $model_client_period->save(false);
                    }
                }
            }
        }
    }

    /**
     *  Update `status_id` from "STATUS_PAYMENT_LINKED_TO_PERIODS" to "STATUS_NOT_DONE"
     *
     * @param ClientPaymentNotPaid $model_not_paid
     * @return void
     */
    private function updateNotPaid(ClientPaymentNotPaid $model_not_paid)
    {
        $model_not_paid->status_id = ClientPaymentNotPaid::STATUS_NOT_DONE;
        $model_not_paid->save(false);
    }

    /**
     *  Delete rows with old `payment_id`
     *
     * @param $model_ccpp
     * @return void
     */
    private function deleteCCPP($model_ccpp)
    {
        if (!empty($model_ccpp)) {
            foreach ($model_ccpp as $ccpp) {
                $ccpp->delete();
            }
        }
    }

    /**
     *  Delete old `payment`
     *
     * @param $model_client_payment
     * @return void
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function deleteOldClientPayment($model_client_payment)
    {
        if (!empty($model_client_payment)) {
            $model_client_payment->delete();
        }
    }

    /**
     * @param $model_ccpp
     * @return array
     */
    private function getPeriodIdsArray($model_ccpp)
    {
        $periodIdsArr = [];

        if (!empty($model_ccpp)) {
            foreach ($model_ccpp as $ccpp) {
                $periodIdsArr[] = $ccpp['period_id'];
            }
        }

        return $periodIdsArr;
    }

    /**
     * @param ClientPaymentNotPaid $model_not_paid
     * @param $card_id
     * @return array|\yii\db\ActiveRecord|null
     */
    private function getCurrentPaymentModel(ClientPaymentNotPaid $model_not_paid, $card_id)
    {
        $client_payment = ClientPayment::find()
            ->where(['card_id' => $card_id])
            ->andWhere(['amount' => $model_not_paid['amount']])
            ->andWhere(['shovar' => $model_not_paid['shovar']])
            ->andWhere(['=', 'date_pay', $model_not_paid['date_pay']]) // Y-m-d H:i:s
            ->one();

        return $client_payment;
    }

    /**
     * @param $payment_id
     * @param $client_id_ts
     * @return array|\yii\db\ActiveRecord[]
     */
    private function getCurrentCCPPModel($payment_id, $client_id_ts)
    {
        $ccpp = ClientContractPaymentPeriod::find()
            ->where(['payment_id' => $payment_id])
            ->andWhere(['client_id' => $client_id_ts])
            ->all();

        return $ccpp;
    }

}
