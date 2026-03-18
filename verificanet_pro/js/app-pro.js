// VERIFICANET - JavaScript Profesional

function showTabPro(tab) {
    document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.form-section-pro').forEach(f => f.classList.remove('active'));
    
    if (tab === 'login') {
        document.querySelector('.tab-btn:first-child').classList.add('active');
        document.getElementById('login-form-pro').classList.add('active');
    } else {
        document.querySelector('.tab-btn:last-child').classList.add('active');
        document.getElementById('register-form-pro').classList.add('active');
    }
    
    hideAlertPro();
}

function showAlertPro(message, type) {
    const alert = document.getElementById('alert-pro');
    if (!alert) return;
    
    alert.textContent = message;
    alert.className = `alert-pro ${type}`;
    alert.style.display = 'block';
    
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}

function hideAlertPro() {
    const alert = document.getElementById('alert-pro');
    if (alert) alert.style.display = 'none';
}

function toggleRegistroTipo() {
    const tipo = document.getElementById('register-tipo').value;
    if (tipo === 'empresa') {
        document.getElementById('registro-particular').style.display = 'none';
        document.getElementById('registro-empresa').style.display = 'block';
    } else {
        document.getElementById('registro-particular').style.display = 'block';
        document.getElementById('registro-empresa').style.display = 'none';
    }
}

async function handleLogin(event) {
    event.preventDefault();
    
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;

    try {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);

        const response = await fetch('/api.php?action=login', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = 'dashboard.html';
        } else {
            showAlertPro(data.error || 'Credenciales incorrectas', 'error');
        }
    } catch (error) {
        showAlertPro('Error de conexión con el servidor', 'error');
        console.error(error);
    }
}

async function handleRegister(event) {
    event.preventDefault();
    
    const tipo = document.getElementById('register-tipo').value;
    const email = document.getElementById('register-email').value;
    const telefono = document.getElementById('register-telefono').value;
    const username = document.getElementById('register-username').value;
    const password = document.getElementById('register-password').value;

    const data = {
        tipo: tipo,
        email: email,
        telefono: telefono,
        username: username,
        password: password
    };

    if (tipo === 'empresa') {
        data.nombre_empresa = document.getElementById('register-empresa').value;
    } else {
        data.nombre = document.getElementById('register-nombre').value;
        data.apellido = document.getElementById('register-apellido').value;
    }

    try {
        const response = await fetch('/api.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showAlertPro('Cuenta creada correctamente. Ya puede iniciar sesión', 'success');
            setTimeout(() => showTabPro('login'), 2000);
        } else {
            showAlertPro(result.error || 'Error al crear la cuenta', 'error');
        }
    } catch (error) {
        showAlertPro('Error de conexión con el servidor', 'error');
        console.error(error);
    }
}

// Mismo código para dashboard que antes (app.js)
function logout() {
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
    
    const section = document.getElementById(sectionId);
    if (section) section.classList.add('active');
    
    const menuItem = document.querySelector(`[onclick="showSection('${sectionId}')"]`);
    if (menuItem) menuItem.classList.add('active');
    
    if (sectionId === 'dashboard') loadDashboardData();
    if (sectionId === 'incidencias') loadIncidencias();
}

async function loadDashboardData() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    try {
        const response = await fetch('/api.php?action=stats');
        const stats = await response.json();
        
        if (user.rol === 'admin' || user.rol === 'empleado') {
            document.getElementById('stat-clientes').textContent = stats.total_clientes || 0;
            document.getElementById('stat-incidencias').textContent = stats.total_incidencias || 0;
            document.getElementById('stat-abiertas').textContent = stats.incidencias_abiertas || 0;
        }
        
        loadIncidencias();
        
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

async function loadIncidencias() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    try {
        let url = '/api.php?action=incidencias';
        
        if (user.rol === 'empleado' && user.id_empleado) {
            url += `&empleado=${user.id_empleado}`;
        } else if (user.rol === 'cliente' && user.id_cliente) {
            url += `&cliente=${user.id_cliente}`;
        }
        
        const response = await fetch(url);
        const incidencias = await response.json();
        
        const tbody = document.getElementById('incidencias-list');
        if (!tbody) return;
        
        if (incidencias.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#999;">No hay incidencias</td></tr>';
            return;
        }
        
        tbody.innerHTML = incidencias.map(inc => `
            <tr>
                <td><strong>#${inc.id_incidencia}</strong></td>
                <td>${inc.titulo}</td>
                <td>${getBadgeEstado(inc.estado)}</td>
                <td>${getBadgePrioridad(inc.prioridad)}</td>
                <td>${new Date(inc.fecha_creacion).toLocaleDateString()}</td>
            </tr>
        `).join('');
        
    } catch (error) {
        console.error('Error cargando incidencias:', error);
    }
}

function getBadgeEstado(estado) {
    const badges = {
        'reportada': '<span class="badge badge-info">Reportada</span>',
        'en_proceso': '<span class="badge badge-warning">En Proceso</span>',
        'resuelta': '<span class="badge badge-success">Resuelta</span>',
        'cerrada': '<span class="badge badge-danger">Cerrada</span>'
    };
    return badges[estado] || estado;
}

function getBadgePrioridad(prioridad) {
    const badges = {
        'baja': '<span class="badge badge-info">Baja</span>',
        'media': '<span class="badge badge-warning">Media</span>',
        'alta': '<span class="badge badge-danger">Alta</span>',
        'critica': '<span class="badge badge-danger">Crítica</span>'
    };
    return badges[prioridad] || prioridad;
}

async function crearIncidencia(event) {
    event.preventDefault();
    
    const user = JSON.parse(localStorage.getItem('user'));
    
    const data = {
        titulo: document.getElementById('inc-titulo').value,
        descripcion: document.getElementById('inc-descripcion').value,
        prioridad: document.getElementById('inc-prioridad').value,
        id_cliente: user.id_cliente
    };
    
    try {
        const response = await fetch('/api.php?action=crear_incidencia', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Incidencia creada correctamente');
            document.getElementById('form-crear-incidencia').reset();
            loadIncidencias();
        } else {
            alert('Error al crear la incidencia');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

window.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('dashboard.html')) {
        const user = localStorage.getItem('user');
        if (!user) {
            window.location.href = 'index.html';
            return;
        }
        
        initDashboard();
    }
});

function initDashboard() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    const userNameEl = document.getElementById('user-name');
    const userRoleEl = document.getElementById('user-role');
    
    if (userNameEl) {
        userNameEl.textContent = user.username;
    }
    
    if (userRoleEl) {
        userRoleEl.textContent = user.rol.charAt(0).toUpperCase() + user.rol.slice(1);
        userRoleEl.className = `user-role role-${user.rol}`;
    }
    
    setupMenuByRole(user.rol);
    loadDashboardData();
}

function setupMenuByRole(rol) {
    showSection('dashboard');
    
    if (rol === 'cliente') {
        const hideItems = document.querySelectorAll('[data-role-required]');
        hideItems.forEach(item => {
            const required = item.getAttribute('data-role-required');
            if (!required.includes(rol)) {
                item.style.display = 'none';
            }
        });
    }
}
