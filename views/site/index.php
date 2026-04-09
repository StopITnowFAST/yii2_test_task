<?php

/** @var yii\web\View $this */

use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Короткая ссылка и QR';

$this->registerJs(
    'window.shortLinkConfig = ' . Json::encode([
        'shortenUrl' => Url::to(['/site/shorten']),
        'csrfParam' => Yii::$app->request->csrfParam,
        'csrfToken' => Yii::$app->request->csrfToken,
    ]) . ';',
    \yii\web\View::POS_HEAD
);
?>

<div class="site-index py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h2 mb-4 text-center">Сервис коротких ссылок и QR</h1>
            <p class="text-muted text-center mb-4">
                Вставьте полный URL (с <code>http://</code> или <code>https://</code>).
                Проверка и создание ссылки выполняются без перезагрузки страницы.
            </p>

            <form id="short-link-form" class="card shadow-sm mb-4" action="#" method="post">
                <div class="card-body">
                    <label for="short-link-input" class="form-label">Адрес ссылки</label>
                    <div class="input-group input-group-lg">
                        <input type="url"
                               class="form-control"
                               id="short-link-input"
                               name="url"
                               placeholder="https://example.com/page"
                               autocomplete="url"
                               required>
                        <button type="submit" class="btn btn-primary" id="short-link-submit">OK</button>
                    </div>
                </div>
            </form>

            <div id="short-link-message" class="alert d-none" role="alert"></div>

            <div id="short-link-result" class="card shadow-sm d-none">
                <div class="card-body text-center">
                    <p class="mb-3">Короткая ссылка (в QR закодирован этот же адрес):</p>
                    <p class="mb-3">
                        <a href="#" id="short-link-url" class="fw-bold text-break" target="_blank" rel="noopener noreferrer"></a>
                    </p>
                    <div class="d-inline-block p-2 bg-white border rounded">
                        <img id="short-link-qr" src="" alt="" width="220" height="220" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
