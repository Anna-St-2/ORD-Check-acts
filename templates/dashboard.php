<?php include 'header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <!-- Боковая панель -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="/dashboard">
                            <i class="bi bi-house"></i> Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard?action=new_check">
                            <i class="bi bi-plus-circle"></i> Новая проверка
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="/ORD-Check-acts/login?action=logout">
                            <i class="bi bi-box-arrow-right"></i> Выйти
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Основной контент -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Система проверки актов ОРД</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">Привет, <?= $_SESSION['username'] ?></span>
                </div>
            </div>

            <!-- Вкладки -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="history-tab" data-bs-toggle="tab" data-bs-target="#history"
                        type="button" role="tab">История проверок</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="new-check-tab" data-bs-toggle="tab" data-bs-target="#new-check"
                        type="button" role="tab">Новая проверка</button>
                </li>
            </ul>

            <div class="tab-content pt-3" id="myTabContent">
                <!-- Вкладка "История проверок" -->
                <div class="tab-pane fade show active" id="history" role="tabpanel">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Здесь будет отображаться история всех проверок
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дата</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Нет выполненных проверок</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Вкладка "Новая проверка" -->
                <div class="tab-pane fade" id="new-check" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5>Загрузка файлов для проверки</h5>
                        </div>
                        <div class="card-body">
                            <form id="uploadForm" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="ordersFile" class="form-label">Файл с Заказами</label>
                                        <input type="file" class="form-control" id="ordersFile" name="ordersFile"
                                            accept=".xlsx,.xls,.csv" required>
                                        <small class="text-muted">Поддерживаются форматы: .xlsx, .xls, .csv</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="mediaplansFile" class="form-label">Файл с Медиапланами</label>
                                        <input type="file" class="form-control" id="mediaplansFile"
                                            name="mediaplansFile" accept=".xlsx,.xls,.csv" required>
                                        <small class="text-muted">Поддерживаются форматы: .xlsx, .xls, .csv</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="actsFile" class="form-label">Файл с Актами</label>
                                        <input type="file" class="form-control" id="actsFile" name="actsFile"
                                            accept=".xlsx,.xls,.csv" required>
                                        <small class="text-muted">Поддерживаются форматы: .xlsx, .xls, .csv</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="autoTranslate" checked>
                                            <label class="form-check-label" for="autoTranslate">
                                                Автоматически перевести заголовки (замена значений)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="filterORD" checked>
                                            <label class="form-check-label" for="filterORD">
                                                Удалить строки с ORD = "Нет"
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Загрузить и начать проверку</button>
                                <div id="uploadProgress" class="mt-3" style="display: none;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <p class="mt-2 text-muted" id="progressText">Загрузка файлов...</p>
                                </div>
                            </form>

                            <div id="fileStatus" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>