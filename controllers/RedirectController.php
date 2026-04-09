<?php

namespace app\controllers;

use app\models\ShortLink;
use app\models\ShortLinkClick;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class RedirectController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionGo(string $code)
    {
        $link = ShortLink::findByCode($code);
        if ($link === null) {
            throw new NotFoundHttpException('Ссылка не найдена.');
        }

        $ip = Yii::$app->request->userIP;
        if ($ip === null || $ip === '') {
            $ip = '0.0.0.0';
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $link->updateCounters(['click_count' => 1]);

            $click = new ShortLinkClick();
            $click->short_link_id = $link->id;
            $click->ip = $ip;
            $click->created_at = time();
            $click->save(false);
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
        }

        return Yii::$app->response->redirect($link->original_url);
    }
}
