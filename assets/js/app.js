// Глобальные функции
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

function showStatus(type, message) {
    const statusDiv = document.getElementById('fileStatus');
    if (!statusDiv) return;
    
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    const icon = type === 'success' ? 'fa-check-circle' : 
                type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    statusDiv.innerHTML = `
        <div class="alert ${alertClass}">
            <span class="alert-icon"><i class="fas ${icon}"></i></span>
            ${message}
        </div>
    `;
}