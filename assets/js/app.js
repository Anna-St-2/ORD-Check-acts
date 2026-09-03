document.addEventListener('DOMContentLoaded', function() {
    // Загрузка файлов
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Проверяем, что все файлы выбраны
            const ordersFile = document.getElementById('ordersFile');
            const mediaplansFile = document.getElementById('mediaplansFile');
            const actsFile = document.getElementById('actsFile');
            
            if (!ordersFile.files[0] || !mediaplansFile.files[0] || !actsFile.files[0]) {
                showStatus('error', 'Пожалуйста, выберите все три файла');
                return;
            }
            
            const formData = new FormData(this);
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = progressDiv.querySelector('.progress-bar');
            const progressText = document.getElementById('progressText');
            const statusDiv = document.getElementById('fileStatus');
            
            // Показываем прогресс
            progressDiv.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Подготовка к загрузке...';
            statusDiv.innerHTML = '';
            
            // Отправляем AJAX запрос
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/ORD-Check-acts/api/upload.php', true);
            
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressText.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Загрузка файлов: ${percent}%`;
                }
            };
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            progressBar.style.width = '100%';
                            progressText.innerHTML = '<i class="fas fa-check-circle" style="color:#10B981;"></i> Файлы успешно загружены!';
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-success');
                            
                            showStatus('success', 'Файлы загружены. Перенаправление...');
                            
                            if (response.redirect) {
                                setTimeout(() => {
                                    window.location.href = response.redirect;
                                }, 1500);
                            }
                        } else {
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-danger');
                            progressText.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;"></i> Ошибка загрузки';
                            
                            let errorMsg = response.error || 'Не удалось загрузить файлы';
                            if (response.errors) {
                                errorMsg += '<ul>';
                                response.errors.forEach(err => {
                                    errorMsg += '<li>' + err + '</li>';
                                });
                                errorMsg += '</ul>';
                            }
                            showStatus('error', errorMsg);
                        }
                    } catch(e) {
                        console.error('Ошибка парсинга ответа:', e);
                        showStatus('error', 'Ошибка обработки ответа сервера');
                    }
                } else {
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-danger');
                    progressText.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;"></i> Ошибка сервера';
                    showStatus('error', 'Ошибка сервера при загрузке файлов');
                }
            };
            
            xhr.onerror = function() {
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-danger');
                progressText.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;"></i> Ошибка соединения';
                showStatus('error', 'Ошибка соединения с сервером');
            };
            
            xhr.send(formData);
        });
    }
    
    // Функция отображения статуса
    function showStatus(type, message) {
        const statusDiv = document.getElementById('fileStatus');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        statusDiv.innerHTML = `
            <div class="alert ${alertClass}">
                <span class="alert-icon"><i class="fas ${icon}"></i></span>
                ${message}
            </div>
        `;
    }
    
    // Загрузка истории при клике на вкладку
    const historyTab = document.getElementById('history-tab');
    if (historyTab) {
        historyTab.addEventListener('click', function() {
            loadHistory();
        });
    }
    
    // Если вкладка активна при загрузке - загружаем историю
    if (document.getElementById('history-tab')?.classList.contains('active')) {
        loadHistory();
    }
    
    function loadHistory() {
        const tbody = document.getElementById('historyBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        Загрузка истории...
                    </td>
                </tr>
            `;
            
            fetch('/ORD-Check-acts/api/history.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.checks.length > 0) {
                        let html = '';
                        data.checks.forEach(check => {
                            const statusMap = {
                                'new': '<span class="badge badge-secondary">Новая</span>',
                                'processing': '<span class="badge badge-warning">Обработка</span>',
                                'completed': '<span class="badge badge-success">Завершена</span>',
                                'error': '<span class="badge badge-danger">Ошибка</span>'
                            };
                            
                            html += `
                                <tr>
                                    <td><strong>#${check.id}</strong></td>
                                    <td>${new Date(check.created_at).toLocaleString('ru-RU')}</td>
                                    <td>${statusMap[check.status] || check.status}</td>
                                    <td>${check.files_count || 0}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewCheck(${check.id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCheck(${check.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                                    Нет выполненных проверок
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки истории:', error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger py-4">
                                <i class="fas fa-times-circle" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                                Ошибка загрузки истории
                            </td>
                        </tr>
                    `;
                });
        }
    }
});

// Глобальные функции
function viewCheck(id) {
    window.location.href = '/ORD-Check-acts/result?id=' + id;
}

function deleteCheck(id) {
    if (confirm('Удалить проверку #' + id + '?')) {
        alert('Функция удаления будет добавлена позже');
    }
}