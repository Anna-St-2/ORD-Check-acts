document.addEventListener('DOMContentLoaded', function() {
    // Обработка загрузки файлов
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = progressDiv.querySelector('.progress-bar');
            const progressText = document.getElementById('progressText');
            
            // Показываем прогресс
            progressDiv.style.display = 'block';
            progressBar.style.width = '0%';
            progressText.textContent = 'Начинаем загрузку файлов...';
            
            // Имитация загрузки (позже заменим на реальный AJAX)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                progressBar.style.width = progress + '%';
                
                if (progress < 50) {
                    progressText.textContent = 'Загрузка файлов на сервер...';
                } else if (progress < 80) {
                    progressText.textContent = 'Обработка файлов...';
                } else if (progress < 100) {
                    progressText.textContent = 'Подготовка к проверке...';
                }
                
                if (progress >= 100) {
                    clearInterval(interval);
                    progressText.textContent = 'Файлы успешно загружены!';
                    progressBar.classList.remove('progress-bar-animated');
                    progressBar.classList.add('bg-success');
                    
                    // Показываем сообщение об успехе
                    const statusDiv = document.getElementById('fileStatus');
                    statusDiv.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Успешно!</strong> Файлы загружены и готовы к проверке.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    
                    // Через секунду перенаправляем на страницу проверки
                    setTimeout(() => {
                        window.location.href = '/check?step=2';
                    }, 2000);
                }
            }, 500);
        });
    }
    
    // Обработка переключения вкладок
    const historyTab = document.getElementById('history-tab');
    const newCheckTab = document.getElementById('new-check-tab');
    
    if (historyTab) {
        historyTab.addEventListener('click', function() {
            // Загружаем историю проверок через AJAX
            loadHistory();
        });
    }
    
    // Функция загрузки истории
    function loadHistory() {
        const historyContainer = document.querySelector('#history .table-responsive tbody');
        if (historyContainer) {
            // Здесь будет AJAX запрос на получение истории
            // Пока просто показываем заглушку
            historyContainer.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">Загрузка истории проверок...</td>
                </tr>
            `;
            
            // Имитация загрузки
            setTimeout(() => {
                historyContainer.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted">Нет выполненных проверок</td>
                    </tr>
                `;
            }, 1000);
        }
    }
});