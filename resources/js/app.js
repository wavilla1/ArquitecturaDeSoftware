import './bootstrap';

const navToggle = document.querySelector('.nav-toggle');
const navPanel = document.querySelector('.nav-panel');

navToggle?.addEventListener('click', () => {
    const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
    navToggle.setAttribute('aria-expanded', String(!isOpen));
    navPanel?.classList.toggle('open', !isOpen);
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Procesando…';
        }
    });
});

const nameInput = document.querySelector('#collection-name');
const previewName = document.querySelector('#preview-name');
const previewMonogram = document.querySelector('#preview-monogram');
const preview = document.querySelector('#mint-preview');
const colorFrom = document.querySelector('#palette-from');
const colorTo = document.querySelector('#palette-to');

const updatePreview = () => {
    const name = nameInput?.value.trim() || 'Tu colección';
    if (previewName) previewName.textContent = name;
    if (previewMonogram) previewMonogram.textContent = name.charAt(0).toUpperCase();
    if (preview && colorFrom && colorTo) {
        preview.style.setProperty('--art-start', colorFrom.value);
        preview.style.setProperty('--art-end', colorTo.value);
    }
};

[nameInput, colorFrom, colorTo].forEach((input) => input?.addEventListener('input', updatePreview));

const toast = document.querySelector('.toast');
if (toast) window.setTimeout(() => toast.classList.add('toast-hidden'), 4200);
