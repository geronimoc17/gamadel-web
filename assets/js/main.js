document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------
    // 1. FUNCIONALIDAD DEL MENÚ HAMBURGUESA (Móvil)
    // ----------------------------------------------------
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    const navLinks = document.querySelectorAll('.main-nav a');

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('open');
        });

        // Cerrar el menú al hacer clic en un link (útil en móviles)
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                // Solo cerrar si el menú está visible/abierto
                if (mainNav.classList.contains('open')) {
                    mainNav.classList.remove('open');
                }
            });
        });
    }

    // ----------------------------------------------------
    // 2. INICIALIZACIÓN DE AOS (Efectos al hacer scroll)
    // ----------------------------------------------------
    // NOTA: La inicialización de AOS debe estar en el HTML 
    // <script>AOS.init({ duration: 800, once: true });</script>

    
    // ----------------------------------------------------
    // 3. LÓGICA DEL FORMULARIO DE CONTACTO (index.html - SIMULACIÓN)
    // ----------------------------------------------------
    const contactForm = document.getElementById('main-contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(event) {
            
            // Prevenir el envío real para simular un proceso de backend
            event.preventDefault(); 
            
            // Validación básica (ya cubierta por 'required', pero la mantenemos)
            let isValid = true;
            contactForm.querySelectorAll('[required]').forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                }
            });

            if (isValid) {
                const successMessage = document.getElementById('success-message');
                
                // Ocultar el formulario y mostrar el mensaje de éxito
                contactForm.style.display = 'none';
                successMessage.style.display = 'block';
                
                // Limpiar campos después de simular el envío
                contactForm.reset(); 
                
                // En un sitio real, aquí iría la llamada AJAX al servidor.
            } else {
                alert("Por favor, completa todos los campos obligatorios del formulario de contacto.");
            }
        });
    }

    // ----------------------------------------------------
    // 4. LÓGICA DEL FORMULARIO DE TRABAJÁ CON NOSOTROS (ENVÍO REAL AJAX)
    // ----------------------------------------------------
    const jobForm = document.getElementById('job-form');
    if (jobForm) {
        
        const jobSubmitButton = jobForm.querySelector('.btn-full-width');
        
        jobForm.addEventListener('submit', function(event) {
            
            // Prevenir el envío tradicional para usar AJAX
            event.preventDefault(); 
            
            // Validación front-end (ya existente)
            let isValid = true;
            
            // 1. Verificar campos requeridos
            jobForm.querySelectorAll('[required]').forEach(input => {
                if (input.type === 'checkbox' && !input.checked) {
                    isValid = false;
                } else if (input.type !== 'checkbox' && !input.value.trim()) {
                    isValid = false;
                }
            });

            // 2. Validación de tipo de archivo CV
            const fileInput = document.getElementById('cv-file');
            if (fileInput.files.length === 0) {
                isValid = false;
            } else if (fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                const extension = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
                const allowedExtensions = ['.pdf', '.doc', '.docx'];
                
                if (!allowedExtensions.includes(extension)) {
                    // Si el archivo no es válido, alertamos y marcamos como inválido
                    alert("Por favor, sube un archivo con formato PDF, DOC o DOCX.");
                    isValid = false;
                }
            }
            
            if (!isValid) {
                 alert("Por favor, completa todos los campos obligatorios y acepta el aviso legal, y sube un CV válido.");
                 return;
            }

            // -----------------------------------------------------
            // ENVÍO REAL USANDO FETCH (AJAX)
            // -----------------------------------------------------

            const formData = new FormData(jobForm);
            const successMessage = document.getElementById('job-success-message');

            // Deshabilitar botón para evitar envíos dobles y dar feedback
            jobSubmitButton.textContent = 'Enviando...';
            jobSubmitButton.disabled = true;
            
            // Revertir el estado de display para asegurar que el formulario esté visible en caso de reintento
            jobForm.style.display = 'block'; 
            successMessage.style.display = 'none';


            fetch(jobForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                 // Verificar si la respuesta es JSON antes de parsear
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    // Si no es JSON (ej. error de PHP/servidor), generar un error
                    throw new Error('Respuesta no JSON. Posible Error 500 del servidor.');
                }
            })
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    jobForm.style.display = 'none';
                    successMessage.style.display = 'block';
                } else {
                    // Mostrar error que viene del servidor (PHP)
                    alert('Error en el envío: ' + data.message);
                }
            })
            .catch(error => {
                alert('Hubo un error de conexión o servidor. Inténtalo más tarde.');
                console.error('Error:', error);
            })
            .finally(() => {
                // Restaurar el botón en caso de error o si el formulario sigue visible
                if (jobForm.style.display !== 'none') {
                    jobSubmitButton.textContent = 'Enviar Postulación';
                    jobSubmitButton.disabled = false;
                }
            });
        });
    }
});