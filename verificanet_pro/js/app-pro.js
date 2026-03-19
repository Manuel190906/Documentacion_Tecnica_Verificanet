// VERIFICANET - JavaScript con dashboards diferenciados por rol

// ==========================================
// AUTENTICACIÓN
// ==========================================

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
    setTimeout(() => alert.style.display = 'none', 5000);
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
            headers: { 'Content-Type': 'application/json' },
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

function logout() {
    localStorage.removeItem('user');
    window.location.href = 'index.html';
}

// ==========================================
// DASHBOARD - INICIALIZACIÓN
// ==========================================

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
    
    document.getElementById('user-name').textContent = user.nombre || user.username;
    document.getElementById('user-role').textContent = getRoleName(user.rol);
    
    setupMenuByRole(user.rol);
    loadDashboardByRole(user.rol);
}

function getRoleName(rol) {
    const roles = {
        'admin': 'Jefe Técnico',
        'empleado': 'Empleado',
        'cliente': 'Cliente'
    };
    return roles[rol] || rol;
}

// ==========================================
// MENÚS POR ROL
// ==========================================

function setupMenuByRole(rol) {
    const menu = document.getElementById('main-menu');
    
    if (rol === 'admin') {
        menu.innerHTML = `
            <div class="menu-item active" onclick="showSection('dashboard')">📊 Dashboard</div>
            <div class="menu-item" onclick="showSection('incidencias')">🎫 Todas las Incidencias</div>
            <div class="menu-item" onclick="showSection('asignar')">👥 Asignar Incidencias</div>
        `;
    } else if (rol === 'empleado') {
        menu.innerHTML = `
            <div class="menu-item active" onclick="showSection('dashboard')">📊 Dashboard</div>
            <div class="menu-item" onclick="showSection('crear-incidencia')">➕ Nueva Incidencia</div>
            <div class="menu-item" onclick="showSection('incidencias')">🎫 Mis Incidencias</div>
        `;
    } else if (rol === 'cliente') {
        menu.innerHTML = `
            <div class="menu-item active" onclick="showSection('dashboard')">📊 Dashboard</div>
            <div class="menu-item" onclick="showSection('servicios')">📦 Mis Servicios</div>
            <div class="menu-item" onclick="showSection('contratos')">📄 Mis Contratos</div>
        `;
    }
}

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
    
    const section = document.getElementById(sectionId);
    if (section) section.classList.add('active');
    
    const menuItem = document.querySelector(`[onclick="showSection('${sectionId}')"]`);
    if (menuItem) menuItem.classList.add('active');
    
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (sectionId === 'dashboard') {
        loadDashboardByRole(user.rol);
    } else if (sectionId === 'incidencias') {
        loadIncidencias();
    } else if (sectionId === 'servicios') {
        loadServicios();
    } else if (sectionId === 'contratos') {
        loadContratos();
    } else if (sectionId === 'crear-incidencia') {
        loadClientes();
    } else if (sectionId === 'asignar') {
        loadIncidenciasSinAsignar();
    }
}

// ==========================================
// DASHBOARDS POR ROL
// ==========================================

async function loadDashboardByRole(rol) {
    if (rol === 'admin') {
        await loadAdminDashboard();
    } else if (rol === 'empleado') {
        await loadEmpleadoDashboard();
    } else if (rol === 'cliente') {
        await loadClienteDashboard();
    }
}

// DASHBOARD ADMIN
async function loadAdminDashboard() {
    try {
        const response = await fetch('/api.php?action=stats');
        const stats = await response.json();
        
        document.getElementById('stats-container').innerHTML = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">${stats.total_incidencias || 0}</div>
                    <div class="stat-label">Incidencias Totales</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-number">${stats.incidencias_abiertas || 0}</div>
                    <div class="stat-label">Abiertas</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-number">${stats.incidencias_resueltas || 0}</div>
                    <div class="stat-label">Resueltas</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-number">${stats.incidencias_sin_asignar || 0}</div>
                    <div class="stat-label">Sin Asignar</div>
                </div>
            </div>
        `;
        
        const respInc = await fetch('/api.php?action=incidencias');
        const incidencias = await respInc.json();
        
        document.getElementById('dashboard-content').innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h2>Últimas Incidencias</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${incidencias.slice(0, 5).map(inc => `
                            <tr>
                                <td><strong>#${inc.id_incidencia}</strong></td>
                                <td>${inc.titulo}</td>
                                <td>${inc.cliente_nombre_completo || 'N/A'}</td>
                                <td>${getBadgeEstado(inc.estado)}</td>
                                <td>${getBadgePrioridad(inc.prioridad)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } catch (error) {
        console.error('Error:', error);
    }
}

// DASHBOARD EMPLEADO
async function loadEmpleadoDashboard() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    try {
        const response = await fetch(`/api.php?action=incidencias&empleado=${user.id_empleado}`);
        const incidencias = await response.json();
        
        const abiertas = incidencias.filter(i => i.estado !== 'resuelta' && i.estado !== 'cerrada').length;
        const resueltas = incidencias.filter(i => i.estado === 'resuelta').length;
        
        document.getElementById('stats-container').innerHTML = `
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">${incidencias.length}</div>
                    <div class="stat-label">Mis Incidencias</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-number">${abiertas}</div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-number">${resueltas}</div>
                    <div class="stat-label">Resueltas</div>
                </div>
            </div>
        `;
        
        document.getElementById('dashboard-content').innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h2>Mis Incidencias Asignadas</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${incidencias.length === 0 ? 
                            '<tr><td colspan="5" style="text-align:center; padding: 40px;">No tienes incidencias asignadas</td></tr>' :
                            incidencias.slice(0, 5).map(inc => `
                                <tr>
                                    <td><strong>#${inc.id_incidencia}</strong></td>
                                    <td>${inc.titulo}</td>
                                    <td>${inc.cliente_nombre_completo || 'N/A'}</td>
                                    <td>${getBadgeEstado(inc.estado)}</td>
                                    <td>${getBadgePrioridad(inc.prioridad)}</td>
                                </tr>
                            `).join('')
                        }
                    </tbody>
                </table>
            </div>
        `;
    } catch (error) {
        console.error('Error:', error);
    }
}

// DASHBOARD CLIENTE
async function loadClienteDashboard() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    try {
        const response = await fetch(`/api.php?action=contratos&cliente=${user.id_cliente}`);
        const contratos = await response.json();
        
        document.getElementById('stats-container').innerHTML = `
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-number">${contratos.length}</div>
                    <div class="stat-label">Servicios Activos</div>
                </div>
            </div>
        `;
        
        document.getElementById('dashboard-content').innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h2>Sus Servicios Contratados</h2>
                </div>
                ${contratos.length === 0 ? 
                    '<p style="text-align:center; padding: 40px;">No tiene servicios contratados</p>' :
                    contratos.map(c => `
                        <div class="service-box">
                            <h3>${c.servicio_nombre}</h3>
                            <p>${c.servicio_descripcion}</p>
                            <p><strong>Estado:</strong> ${c.estado}</p>
                            <p><strong>Fecha inicio:</strong> ${new Date(c.fecha_inicio).toLocaleDateString()}</p>
                        </div>
                    `).join('')
                }
            </div>
        `;
    } catch (error) {
        console.error('Error:', error);
    }
}

// ==========================================
// CLIENTE - SERVICIOS Y CONTRATOS
// ==========================================

async function loadServicios() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    try {
        const response = await fetch(`/api.php?action=servicios&cliente=${user.id_cliente}`);
        const servicios = await response.json();
        
        document.getElementById('servicios-content').innerHTML = servicios.length === 0 ?
            '<div class="card"><p style="text-align:center; padding: 40px;">No hay servicios disponibles</p></div>' :
            servicios.map(s => `
                <div class="card">
                    <h3>${s.nombre}</h3>
                    <p>${s.descripcion}</p>
                    <p><strong>Tipo:</strong> ${s.tipo_servicio}</p>
                    <p class="service-price">${s.precio_base}€ / mes</p>
                </div>
            `).join('');
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadContratos() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    try {
        const response = await fetch(`/api.php?action=contratos&cliente=${user.id_cliente}`);
        const contratos = await response.json();
        
        document.getElementById('contratos-content').innerHTML = `
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Fecha Inicio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${contratos.length === 0 ?
                            '<tr><td colspan="3" style="text-align:center; padding: 40px;">No tiene contratos activos</td></tr>' :
                            contratos.map(c => `
                                <tr>
                                    <td><strong>${c.servicio_nombre}</strong></td>
                                    <td>${new Date(c.fecha_inicio).toLocaleDateString()}</td>
                                    <td><span class="badge badge-success">${c.estado}</span></td>
                                </tr>
                            `).join('')
                        }
                    </tbody>
                </table>
            </div>
        `;
    } catch (error) {
        console.error('Error:', error);
    }
}

// ==========================================
// INCIDENCIAS
// ==========================================

async function loadIncidencias() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    try {
        let url = '/api.php?action=incidencias';
        
        if (user.rol === 'empleado' && user.id_empleado) {
            url += `&empleado=${user.id_empleado}`;
        }
        
        const response = await fetch(url);
        const incidencias = await response.json();
        
        const tbody = document.getElementById('incidencias-list');
        if (!tbody) return;
        
        if (incidencias.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 40px;">No hay incidencias</td></tr>';
            return;
        }
        
        tbody.innerHTML = incidencias.map(inc => `
            <tr>
                <td><strong>#${inc.id_incidencia}</strong></td>
                <td>${inc.titulo}</td>
                <td>${inc.cliente_nombre_completo || 'N/A'}</td>
                <td>${getBadgeEstado(inc.estado)}</td>
                <td>${getBadgePrioridad(inc.prioridad)}</td>
                <td>${inc.empleado_nombre ? inc.empleado_nombre : '<em>Sin asignar</em>'}</td>
                <td>
                    ${user.rol === 'empleado' && inc.id_empleado_asignado == user.id_empleado ? 
                        `<button class="btn btn-sm btn-success" onclick="cambiarEstado(${inc.id_incidencia}, '${inc.estado}')">Cambiar Estado</button>` : 
                        ''}
                    ${user.rol === 'admin' ? 
                        `<button class="btn btn-sm btn-danger" onclick="borrarIncidencia(${inc.id_incidencia})">Borrar</button>` : 
                        ''}
                </td>
            </tr>
        `).join('');
        
    } catch (error) {
        console.error('Error:', error);
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

// ==========================================
// EMPLEADO - CREAR INCIDENCIA
// ==========================================

async function loadClientes() {
    try {
        const response = await fetch('/api.php?action=clientes');
        const clientes = await response.json();
        
        const select = document.getElementById('inc-cliente');
        if (!select) return;
        
        select.innerHTML = '<option value="">Seleccione un cliente</option>' +
            clientes.map(c => `<option value="${c.id_cliente}">${c.nombre_completo || c.nombre_empresa}</option>`).join('');
    } catch (error) {
        console.error('Error:', error);
    }
}

async function crearIncidencia(event) {
    event.preventDefault();
    
    const data = {
        titulo: document.getElementById('inc-titulo').value,
        descripcion: document.getElementById('inc-descripcion').value,
        prioridad: document.getElementById('inc-prioridad').value,
        id_cliente: document.getElementById('inc-cliente').value
    };
    
    try {
        const response = await fetch('/api.php?action=crear_incidencia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        const alert = document.getElementById('alert-incidencia');
        if (result.success) {
            alert.textContent = '✓ Incidencia creada correctamente. ID: #' + result.id_incidencia;
            alert.className = 'alert success';
            alert.style.display = 'block';
            
            document.getElementById('form-crear-incidencia').reset();
            
            setTimeout(() => alert.style.display = 'none', 3000);
        } else {
            alert.textContent = '✗ Error al crear la incidencia';
            alert.className = 'alert error';
            alert.style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// ==========================================
// EMPLEADO - CAMBIAR ESTADO
// ==========================================

async function cambiarEstado(idIncidencia, estadoActual) {
    const estados = {
        'reportada': 'en_proceso',
        'en_proceso': 'resuelta',
        'resuelta': 'cerrada'
    };
    
    const nuevoEstado = estados[estadoActual];
    if (!nuevoEstado) {
        alert('No se puede cambiar más el estado');
        return;
    }
    
    try {
        const response = await fetch('/api.php?action=actualizar_incidencia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_incidencia: idIncidencia,
                estado: nuevoEstado
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadIncidencias();
        } else {
            alert('Error al actualizar');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// ==========================================
// ADMIN - ASIGNAR Y BORRAR
// ==========================================

async function loadIncidenciasSinAsignar() {
    try {
        const [respInc, respEmp] = await Promise.all([
            fetch('/api.php?action=incidencias&sin_asignar=1'),
            fetch('/api.php?action=empleados')
        ]);
        
        const incidencias = await respInc.json();
        const empleados = await respEmp.json();
        
        const container = document.getElementById('incidencias-sin-asignar');
        
        if (incidencias.length === 0) {
            container.innerHTML = '<p style="text-align:center; padding: 40px;">Todas las incidencias están asignadas</p>';
            return;
        }
        
        container.innerHTML = incidencias.map(inc => `
            <div class="service-box">
                <h3>#${inc.id_incidencia} - ${inc.titulo}</h3>
                <p><strong>Cliente:</strong> ${inc.cliente_nombre_completo || 'N/A'}</p>
                <p><strong>Prioridad:</strong> ${getBadgePrioridad(inc.prioridad)}</p>
                <div class="form-group">
                    <label>Asignar a:</label>
                    <select id="emp-${inc.id_incidencia}" class="form-control">
                        <option value="">Seleccione empleado</option>
                        ${empleados.map(e => `<option value="${e.id_empleado}">${e.nombre} ${e.apellido}</option>`).join('')}
                    </select>
                    <button class="btn btn-primary" style="margin-top: 10px;" onclick="asignarIncidencia(${inc.id_incidencia})">Asignar</button>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Error:', error);
    }
}

async function asignarIncidencia(idIncidencia) {
    const empleadoId = document.getElementById(`emp-${idIncidencia}`).value;
    
    if (!empleadoId) {
        alert('Seleccione un empleado');
        return;
    }
    
    try {
        const response = await fetch('/api.php?action=asignar_incidencia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_incidencia: idIncidencia,
                id_empleado: empleadoId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const alert = document.getElementById('alert-asignar');
            alert.textContent = '✓ Incidencia asignada correctamente';
            alert.className = 'alert success';
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
                loadIncidenciasSinAsignar();
            }, 2000);
        } else {
            alert('Error al asignar');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function borrarIncidencia(idIncidencia) {
    if (!confirm('¿Está seguro de borrar esta incidencia?')) {
        return;
    }
    
    try {
        const response = await fetch('/api.php?action=borrar_incidencia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_incidencia: idIncidencia
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadIncidencias();
        } else {
            alert('Error al borrar');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
