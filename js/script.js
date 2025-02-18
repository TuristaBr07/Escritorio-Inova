// Navegação suave para âncoras
document.querySelectorAll('a.nav-link').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const targetID = this.getAttribute('href');
    const target = document.querySelector(targetID);
    if (target) {
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const track = document.querySelector(".clientes-track");
  const clone = track.innerHTML;
  track.innerHTML += clone; // Duplica os itens para criar o efeito infinito

  let speed = 1; // Velocidade do carrossel
  let position = 0;

  function animate() {
    position -= speed;
    if (position <= -track.offsetWidth / 2) {
      position = 0;
    }
    track.style.transform = `translateX(${position}px)`;
    requestAnimationFrame(animate);
  }

  animate();
});

// Adicionar destaque às seções visíveis com IntersectionObserver
if ('IntersectionObserver' in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('highlight');
      } else {
        entry.target.classList.remove('highlight');
      }
    });
  }, { threshold: 0.5 });
  
  document.querySelectorAll('section').forEach(section => {
    observer.observe(section);
  });
} else {
  console.warn('IntersectionObserver não é suportado neste navegador.');
}
  
// Validação do formulário de contato
const form = document.querySelector('.contact-form');
if (form) {
  const feedback = document.createElement('p');
  feedback.style.marginTop = '15px';
  form.appendChild(feedback);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const nome = document.getElementById('nome').value.trim();
    const email = document.getElementById('email').value.trim();
    const mensagem = document.getElementById('mensagem').value.trim();

    if (!nome || !email || !mensagem) {
      feedback.textContent = 'Por favor, preencha todos os campos.';
      feedback.style.color = 'red';
      return;
    }

    if (!validateEmail(email)) {
      feedback.textContent = 'Por favor, insira um endereço de email válido.';
      feedback.style.color = 'red';
      return;
    }

    feedback.textContent = 'Mensagem enviada com sucesso!';
    feedback.style.color = 'green';

    // Envia o formulário após 1 segundo para permitir que o feedback seja visto
    setTimeout(() => {
      form.submit();
    }, 1000);
  });
}

function validateEmail(email) {
  const re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
  return re.test(email);
}

