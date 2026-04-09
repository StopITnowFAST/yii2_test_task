<?php

namespace app\controllers;

use app\components\ShortUrlQrGenerator;
use app\components\UrlReachabilityChecker;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\ShortLink;
use Yii;
use yii\db\Exception as DbException;
use yii\db\IntegrityException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'shorten' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionShorten()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $url = trim((string) Yii::$app->request->post('url', ''));

        $checker = new UrlReachabilityChecker();
        if (!$checker->validateFormat($url)) {
            return [
                'success' => false,
                'message' => 'Указан некорректный URL. Введите полный адрес с протоколом http или https.',
            ];
        }

        if (!$checker->isReachable($url)) {
            return [
                'success' => false,
                'message' => 'Данный URL не доступен',
            ];
        }

        $qr = new ShortUrlQrGenerator();
        $attempts = 20;

        try {
            for ($i = 0; $i < $attempts; $i++) {
                $model = new ShortLink();
                $model->original_url = $url;
                $model->code = ShortLink::generateCode(8);

                try {
                    if ($model->save()) {
                        $shortUrl = Yii::$app->urlManager->createAbsoluteUrl(['/redirect/go', 'code' => $model->code]);

                        return [
                            'success' => true,
                            'shortUrl' => $shortUrl,
                            'qrDataUri' => $qr->createDataUri($shortUrl),
                        ];
                    }

                    $err = $model->getFirstErrors();

                    return [
                        'success' => false,
                        'message' => 'Ошибка сохранения: ' . (empty($err) ? 'неизвестно' : reset($err)),
                    ];
                } catch (IntegrityException $e) {
                    continue;
                }
            }
        } catch (\Throwable $e) {
            if (!($e instanceof DbException) && !($e instanceof \PDOException)) {
                throw $e;
            }
            Yii::error($e, __METHOD__);
            $msg = $e->getMessage();
            $hint = 'Проверьте: расширение pdo_mysql в PHP, запущен ли MySQL/MariaDB, настройки в config/db.php, выполнена ли миграция (php yii migrate).';
            if (stripos($msg, 'could not find driver') !== false) {
                $hint = 'В php.ini включите строку extension=pdo_mysql и перезапустите сервер.';
            }
            if (stripos($msg, 'Access denied') !== false) {
                $hint = 'Неверный логин или пароль к MySQL в config/db.php (или у пользователя root нет доступа без пароля).';
            }

            return [
                'success' => false,
                'message' => 'Ошибка базы данных. ' . $hint,
            ];
        }

        return [
            'success' => false,
            'message' => 'Не удалось создать короткую ссылку. Попробуйте ещё раз.',
        ];
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
