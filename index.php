<?php
// Incluir header de status de login antes de qualquer saída HTML
require_once 'header_status.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyAbroad - Realize Seu Sonho de Estudar no Exterior</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #187bcb;
            --secondary-color: #6c5ce7;
            --accent-color: #fd79a8;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --bg-light: #f8f9fa;
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('dream-clouds.svg') center/cover;
            opacity: 0.1;
            z-index: 1;
        }

        .hero-content {
            text-align: center;
            color: var(--white);
            z-index: 2;
            position: relative;
            max-width: 800px;
            padding: 0 20px;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease-out;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--white);
            color: var(--primary-color);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.3);
        }

        .btn-secondary {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .btn-secondary:hover {
            background: var(--white);
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Features Section */
        .features {
            padding: 80px 20px;
            background: var(--bg-light);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .section-title p {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }

        .feature-card {
            background: var(--white);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .feature-card p {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            padding: 80px 20px;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: var(--white);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 20px;
            background: var(--white);
            text-align: center;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        .cta-content p {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 40px;
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: var(--white);
            padding: 40px 20px;
            text-align: center;
        }

        .footer p {
            opacity: 0.8;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Floating Elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="floating-elements">
            <div class="floating-element">🎓</div>
            <div class="floating-element">✈️</div>
            <div class="floating-element">🌍</div>
        </div>
        
        <div class="hero-content">
            <h1>🌟 Realize Seu Sonho de Estudar no Exterior</h1>
            <p>Descubra as melhores oportunidades de estudo internacional e prepare-se para os testes de proficiência com nossa plataforma completa</p>
            
            <div class="cta-buttons">
                <a href="testes_internacionais.php" class="btn btn-primary">
                    🚀 Explorar Testes
                </a>
                <a href="login.php" class="btn btn-secondary">
                    👤 Fazer Login
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
            </div>
            
            <div class="features-grid">
                <div class="feature-card" onclick="window.location.href='simulador_provas.php'" style="cursor: pointer;">
                    <span class="feature-icon">🎯</span>
                    <h3>Testes Personalizados</h3>
                    <p>Simulados específicos para cada país e tipo de exame, adaptados ao seu nível e objetivos acadêmicos.</p>
                </div>
                
                <div class="feature-card" onclick="window.location.href='testes_internacionais.php'" style="cursor: pointer;">
                    <span class="feature-icon">🌍</span>
                    <h3>Cobertura Global</h3>
                    <p>Informações sobre testes de proficiência para mais de 15 países em 5 continentes diferentes.</p>
                </div>
                
                <div class="feature-card"  onclick="window.location.href='simulador_provas.php#ancora'" style="cursor: pointer;">

                    <span class="feature-icon">📊</span>
                    <h3>Acompanhamento Detalhado</h3>
                    <p>Monitore seu progresso com relatórios detalhados e dicas personalizadas para melhorar seu desempenho.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🎓</span>
                    <h3>Preparação Completa</h3>
                    <p>Informações específicas SAT, MEXT, GMAT, GRE e muito mais.</p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">⚡</span>
                    <h3>Resultados Rápidos</h3>
                    <p>Feedback imediato nos simulados com explicações detalhadas para cada questão.</p>
                </div>
                
                <div class="feature-card" onclick="window.location.href='forum.php'" style="cursor: pointer;">
                    <span class="feature-icon">💡</span>
                    <h3>Dicas de Especialistas</h3>
                    <p>Conteúdo criado por especialistas em educação internacional e ex-estudantes no exterior.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>15+</h3>
                    <p>Países Disponíveis</p>
                </div>
                <div class="stat-item">
                    <h3>30+</h3>
                    <p>Tipos de Exames</p>
                </div>
                <div class="stat-item">
                    <h3>1000+</h3>
                    <p>Questões Práticas</p>
                </div>

            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>🚀 Comece Sua Jornada Hoje</h2>
                <p>Junte-se a milhares de estudantes que já realizaram o sonho de estudar no exterior. Sua aventura internacional começa aqui!</p>
                
                <div class="cta-buttons">
                    <a href="cadastro.php" class="btn btn-primary">
                        ✨ Criar Conta Grátis
                    </a>
                    <a href="testes_internacionais.php" class="btn btn-secondary">
                        🔍 Ver Todos os Testes
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 StudyAbroad. Transformando sonhos em realidade. ✈️🎓</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add scroll effect to hero
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            hero.style.transform = `translateY(${scrolled * 0.5}px)`;
        });

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statItems = entry.target.querySelectorAll('.stat-item h3');
                    statItems.forEach((item, index) => {
                        const finalValue = item.textContent;
                        const numericValue = parseInt(finalValue.replace(/\D/g, ''));
                        const suffix = finalValue.replace(/\d/g, '');
                        
                        let currentValue = 0;
                        const increment = numericValue / 50;
                        
                        const timer = setInterval(() => {
                            currentValue += increment;
                            if (currentValue >= numericValue) {
                                currentValue = numericValue;
                                clearInterval(timer);
                            }
                            item.textContent = Math.floor(currentValue) + suffix;
                        }, 30);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</body>
</html>