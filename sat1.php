<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de Provas</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .exam-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .exam-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4a6fa5;
            padding-bottom: 15px;
        }
        
        .exam-title {
            color: #1a2a6c;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .question-counter {
            color: #666;
            font-size: 1.1rem;
        }
        
        .module-indicator {
            background: #4a6fa5;
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .question-container {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .question-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .question-image:hover {
            transform: scale(1.02);
        }
        
        .alternatives-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .alternative {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .alternative:hover {
            background: #e3f2fd;
            border-color: #4a6fa5;
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .alternative.selected {
            background: #4a6fa5;
            color: white;
            border-color: #3a5a8c;
        }
        
        .alternative-letter {
            background: #4a6fa5;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin-bottom: 10px;
            flex-shrink: 0;
        }
        
        .alternative.selected .alternative-letter {
            background: white;
            color: #4a6fa5;
        }
        
        .alternative-image {
            max-width: 100%;
            max-height: 120px;
            border-radius: 8px;
            margin-bottom: 10px;
            object-fit: contain;
        }
        
        .alternative-text {
            text-align: center;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .numeric-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .numeric-question {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #333;
        }
        
        .numeric-input {
            padding: 12px;
            font-size: 1.1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            width: 150px;
            text-align: center;
            transition: border-color 0.3s ease;
        }
        
        .numeric-input:focus {
            outline: none;
            border-color: #4a6fa5;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
        }
        
        .input-hint {
            font-size: 0.9rem;
            color: #666;
            margin-top: 8px;
            font-style: italic;
        }
        
        .submit-button {
            margin-top: 15px;
            padding: 10px 25px;
            background: #4a6fa5;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-button:hover {
            background: #3a5a8c;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        
        .feedback {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .feedback.show {
            opacity: 1;
        }
        
        .feedback.correct {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .feedback.incorrect {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .next-button {
            background: #4a6fa5;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            display: none;
        }
        
        .next-button:hover {
            background: #3a5a8c;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        
        .next-button.show {
            display: inline-block;
        }
        
        .transition-container {
            display: none;
            text-align: center;
            padding: 40px 20px;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .transition-title {
            color: #1a2a6c;
            font-size: 2.2rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .transition-message {
            font-size: 1.3rem;
            margin-bottom: 30px;
            color: #555;
            line-height: 1.5;
        }
        
        .transition-icon {
            font-size: 4rem;
            color: #4a6fa5;
            margin-bottom: 20px;
        }
        
        .start-module-button {
            background: linear-gradient(135deg, #4a6fa5, #1a2a6c);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 111, 165, 0.3);
        }
        
        .start-module-button:hover {
            background: linear-gradient(135deg, #3a5a8c, #0f1f4a);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(74, 111, 165, 0.4);
        }
        
        .math-transition-container {
            display: none;
            text-align: center;
            padding: 40px 20px;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .math-transition-title {
            color: #1a2a6c;
            font-size: 2.2rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .math-transition-message {
            font-size: 1.3rem;
            margin-bottom: 30px;
            color: #555;
            line-height: 1.5;
        }
        
        .math-transition-icon {
            font-size: 4rem;
            color: #4a6fa5;
            margin-bottom: 20px;
        }
        
        .start-math-button {
            background: linear-gradient(135deg, #b21f1f, #fdbb2d);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(178, 31, 31, 0.3);
        }
        
        .start-math-button:hover {
            background: linear-gradient(135deg, #a01818, #f0a515);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(178, 31, 31, 0.4);
        }
        
        /* Nova tela de transição para o segundo módulo de matemática */
        .math2-transition-container {
            display: none;
            text-align: center;
            padding: 40px 20px;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .math2-transition-title {
            color: #1a2a6c;
            font-size: 2.2rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .math2-transition-message {
            font-size: 1.3rem;
            margin-bottom: 30px;
            color: #555;
            line-height: 1.5;
        }
        
        .math2-transition-icon {
            font-size: 4rem;
            color: #4a6fa5;
            margin-bottom: 20px;
        }
        
        .start-math2-button {
            background: linear-gradient(135deg, #b21f1f, #fdbb2d);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(178, 31, 31, 0.3);
        }
        
        .start-math2-button:hover {
            background: linear-gradient(135deg, #a01818, #f0a515);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(178, 31, 31, 0.4);
        }
        
        .results-container {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .results-title {
            color: #1a2a6c;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        
        /* Estilos para a pontuação no formato SAT */
        .sat-score-container {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            gap: 20px;
        }
        
        .sat-score-section {
            flex: 1;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .sat-score-title {
            font-size: 1.2rem;
            color: #1a2a6c;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .sat-score-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .sat-score-bar-container {
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
        }
        
        .sat-score-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 1.5s ease-in-out;
        }
        
        .rw-score .sat-score-bar {
            background: linear-gradient(90deg, #4a6fa5, #1a2a6c);
        }
        
        .math-score .sat-score-bar {
            background: linear-gradient(90deg, #b21f1f, #fdbb2d);
        }
        
        .sat-total-score {
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            border-radius: 15px;
            color: white;
        }
        
        .sat-total-score-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }
        
        .sat-total-score-value {
            font-size: 3rem;
            font-weight: bold;
        }
        
        .results-message {
            font-size: 1.2rem;
            margin: 20px 0;
        }
        
        .restart-button {
            background: #b21f1f;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .restart-button:hover {
            background: #a01818;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 600px) {
            .alternatives-container {
                grid-template-columns: 1fr;
            }
            
            .exam-title {
                font-size: 1.5rem;
            }
            
            .transition-title, .math-transition-title, .math2-transition-title {
                font-size: 1.8rem;
            }
            
            .transition-message, .math-transition-message, .math2-transition-message {
                font-size: 1.1rem;
            }
            
            .start-module-button, .start-math-button, .start-math2-button {
                padding: 12px 30px;
                font-size: 1.1rem;
            }
            
            .sat-score-container {
                flex-direction: column;
            }
            
            .sat-total-score-value {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="exam-container">
        <div class="exam-header">
            <h1 class="exam-title">Simulador de Provas</h1>
            <div class="question-counter">Questão <span id="current-question">1</span> de <span id="total-questions">33</span></div>
            <div class="module-indicator" id="module-indicator">Módulo 1</div>
        </div>
        
        <div id="question-section">
            <div class="question-container">
                <img id="question-image" src="imagens/prova1/quest1.png" alt="Questão 1" class="question-image">
                
                <div class="alternatives-container" id="alternatives-container">
                    <!-- Alternativas serão inseridas aqui via JavaScript -->
                </div>
                
                <div id="feedback" class="feedback"></div>
                <button id="next-button" class="next-button">Próxima Questão</button>
            </div>
        </div>
        
        <div id="transition-section" class="transition-container">
            <div class="transition-icon">🎉</div>
            <h2 class="transition-title">Módulo 1 Concluído!</h2>
            <p class="transition-message">Parabéns por completar todas as 33 questões do Módulo 1. Agora vamos começar o Módulo 2 com novas questões para continuar seu treinamento.</p>
            <button id="start-module2-button" class="start-module-button">Iniciar Módulo 2</button>
        </div>
        
        <div id="math-transition-section" class="math-transition-container">
            <div class="math-transition-icon">🧮</div>
            <h2 class="math-transition-title">Módulo 2 Concluído!</h2>
            <p class="math-transition-message">Parabéns por completar todas as questões de Leitura e Escrita. Agora vamos começar o Módulo 1 de Matemática.</p>
            <button id="start-math-button" class="start-math-button">Iniciar Módulo de Matemática</button>
        </div>
        
        <!-- Nova tela de transição para o segundo módulo de matemática -->
        <div id="math2-transition-section" class="math2-transition-container">
            <div class="math2-transition-icon">🧮</div>
            <h2 class="math2-transition-title">Módulo de Matemática 1 Concluído!</h2>
            <p class="math2-transition-message">O primeiro módulo de Matemática foi finalizado. Agora será iniciado o segundo módulo, nesse módulo é permitido uso de calculadora.</p>
            <button id="start-math2-button" class="start-math2-button">Iniciar Módulo de Matemática 2</button>
        </div>
        
        <div id="results-section" class="results-container">
            <h2 class="results-title">Resultado Final</h2>
            
            <div class="sat-score-container">
                <div class="sat-score-section rw-score">
                    <div class="sat-score-title">Reading & Writing</div>
                    <div class="sat-score-value" id="rw-score">200</div>
                    <div class="sat-score-bar-container">
                        <div class="sat-score-bar" id="rw-score-bar" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="sat-score-section math-score">
                    <div class="sat-score-title">Math</div>
                    <div class="sat-score-value" id="math-score">200</div>
                    <div class="sat-score-bar-container">
                        <div class="sat-score-bar" id="math-score-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            
            <div class="sat-total-score">
                <div class="sat-total-score-title">Pontuação Total SAT</div>
                <div class="sat-total-score-value" id="total-score">400</div>
            </div>
            
            <p class="results-message" id="results-message"></p>
            <button id="restart-button" class="restart-button">Reiniciar Simulado</button>
        </div>
    </div>
    <script>
        // Dados das questões do Módulo 1
        const questionsModule1 = [
            {
                image: "imagens/prova1/quest1.png",
                alternatives: [
                    { letter: "A", text: "attached" },
                    { letter: "B", text: "collected" },
                    { letter: "C", text: "followed" },
                    { letter: "D", text: "replaced" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/quest2.png",
                alternatives: [
                    { letter: "A", text: "reflect" },
                    { letter: "B", text: "receive" },
                    { letter: "C", text: "evaluate" },
                    { letter: "D", text: "mimic" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest3.png",
                alternatives: [
                    { letter: "A", text: " recognizable " },
                    { letter: "B", text: "intriguing " },
                    { letter: "C", text: "significant " },
                    { letter: "D", text: "useful " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest4.png",
                alternatives: [
                    { letter: "A", text: "  substantial  " },
                    { letter: "B", text: "satisfying " },
                    { letter: "C", text: "unimportant  " },
                    { letter: "D", text: "appropriate  " }
                ],
                correct: "C"
            }
            ,
            {
                image: "imagens/prova1/quest5.png",
                alternatives: [
                    { letter: "A", text: "To reveal the shop owner's conflicted feelings about the new picture  " },
                    { letter: "B", text: "To convey the shop owner's resentment of the person he got the new picture from  " },
                    { letter: "C", text: "To describe the items that the shop owner most highly prizes  " },
                    { letter: "D", text: "To explain differences between the new picture and other pictures in the shop   " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest6.png",
                alternatives: [
                    { letter: "A", text: "The speaker assesses a natural phenomenon, then questions the accuracy of her assessment.   " },
                    { letter: "B", text: "The speaker describes a distinctive sight in nature, then ponders what meaning to attribute to that sight. " },
                    { letter: "C", text: "The speaker presents an outdoor scene, then considers a human behavior occurring within that scene.  " },
                    { letter: "D", text: "The speaker examines her surroundings, then speculates about their influence on her emotional state.   " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/quest7.png",
                alternatives: [
                    { letter: "A", text: "The speaker questions an increasingly prevalent attitude, then summarizes his worldview.    " },
                    { letter: "B", text: "The speaker regrets his isolation from others, then predicts a profound change in society.  " },
                    { letter: "C", text: "The speaker concedes his personal shortcomings, then boasts of his many achievements.   " },
                    { letter: "D", text: "The speaker addresses a criticism leveled against him, then announces a grand ambition of his   " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest8.png",
                alternatives: [
                    { letter: "A", text: "It states the hypothesis that Chang and colleagues had set out to investigate using mimosa trees and B. terrenus.     " },
                    { letter: "B", text: "It presents a generalization that is exemplified by the discussion of the mimosa trees and B. terrenus.   " },
                    { letter: "C", text: "It offers an alternative explanation for the findings of Chang and colleagues. " },
                    { letter: "D", text: " It provides context that clarifies why the species mentioned spread to new locations.   " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/quest9.png",
                alternatives: [
                    { letter: "A", text: "By conceding the importance of hierarchical systems but asserting the greater significance of decentralized collective societies      " },
                    { letter: "B", text: "By disputing the idea that developments in social structures have followed a linear progression through distinct stages    " },
                    { letter: "C", text: "By acknowledging that hierarchical roles likely weren't a part of social systems before the rise of agriculture  " },
                    { letter: "D", text: "By challenging the assumption that groupings of hunter-gatherers were among the earliest forms of social structure    " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/quest10.png",
                alternatives: [
                    { letter: "A", text: "Mary hides in the garden to avoid doing her chores.      " },
                    { letter: "B", text: "Mary is getting bored with pulling up so many weeds in the garden.    " },
                    { letter: "C", text: "Mary is clearing out the garden to create a space to play. " },
                    { letter: "D", text: "Mary feels very satisfied when she's taking care of the garden.     " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest11.png",
                alternatives: [
                    { letter: "A", text: "It becomes increasingly vigorous with the passage of time.       " },
                    { letter: "B", text: "It draws strength from changes in the weather.    " },
                    { letter: "C", text: "It requires proper nourishment in order to thrive.  " },
                    { letter: "D", text: "It perseveres despite challenging circumstances.     " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/quest12.png",
                alternatives: [
                    { letter: "A", text: "Buck has become less social since he began living with Thornton.        " },
                    { letter: "B", text: "Buck mistrusts humans and does his best to avoid them.    " },
                    { letter: "C", text: "Buck has been especially well liked by most of Thornton's friends.  " },
                    { letter: "D", text: "Buck holds Thornton in higher regard than any other person.     " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest13.png",
                alternatives: [
                    { letter: "A", text: "Washington had between 600 and 800 organic farms.        " },
                    { letter: "B", text: "New York had fewer than 800 organic farms.    " },
                    { letter: "C", text: "Wisconsin and Iowa each had between 1,200 and 1,400 organic farms.   " },
                    { letter: "D", text: " Pennsylvania had more than 1,200 organic farms.      " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest14.png",
                alternatives: [
                    { letter: "A", text: "The feathers located on the wings of the migratory fork-tailed flycatchers have a narrower shape than those of the nonmigratory birds, which allows them to fly long distances.      " },
                    { letter: "B", text: "Over several generations, the sound made by the feathers of migratory male fork-tailed flycatchers grows progressively higher pitched relative to that made by the feathers of nonmigratory males.     " },
                    { letter: "C", text: "Fork-tailed flycatchers communicate different messages to each other depending on whether their feathers create high-pitched or low-pitched sounds.    " },
                    { letter: "D", text: "The breeding habits of the migratory and nonmigratory fork-tailed flycatchers remained generally the same over several generations.      " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/quest15.png",
                alternatives: [
                    { letter: "A", text: "iron from SPC dust is 20%.       " },
                    { letter: "B", text: "sodium from OCC dust is 100%     " },
                    { letter: "C", text: "iron from HTC dust is 90%.     " },
                    { letter: "D", text: "sodium from AST dust is 75%.       " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/quest16.png",
                alternatives: [
                    { letter: "A", text: "The first collective I joined included many amazingly talented artists, and we enjoyed each other's company, but because we had a hard time sharing credit and responsibility for our work, the collective didn't last.       " },
                    { letter: "B", text: "We work together, but that doesn't mean that individual projects are equally the work of all of us. Many of our projects are primarily the responsibility of whoever originally proposed the work to the group.    " },
                    { letter: "C", text: "Having worked as a member of a collective for several years, it's sometimes hard to recall what it was like to work alone without the collective's support. But that support encourages my individual expression rather than limits it.      " },
                    { letter: "D", text: "Sometimes an artist from outside the collective will choose to collaborate with us on a project, but all of those projects fit within the larger themes of the work the collective does on its own.      " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest17.png",
                alternatives: [
                    { letter: "A", text: "broccoli grown in soil containing mycorrhizal fungi had a slightly higher average mass than broccoli grown in soil that had been treated to kill fungi.       " },
                    { letter: "B", text: "corn grown in soil containing mycorrhizal fungi had a higher average mass than broccoli grown in soil containing mycorrhizal fungi.      " },
                    { letter: "C", text: "marigolds grown in soil containing mycorrhizal fungi had a much higher average mass than marigolds grown in soil that had been treated to kill fungi.     " },
                    { letter: "D", text: "corn had the highest average mass of all three species grown in soil that had been treated to kill fungi, while marigolds had the lowest.        " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest18.png",
                alternatives: [
                    { letter: "A", text: "is not conclusive evidence that the figure is Venus.         " },
                    { letter: "B", text: "suggests that Venus was often depicted fishing.    " },
                    { letter: "C", text: "eliminates the possibility that the figure is Venus.     " },
                    { letter: "D", text: "would be difficult to account for if the figure is not Venus.   " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest19.png",
                alternatives: [
                    { letter: "A", text: "people's stories          " },
                    { letter: "B", text: "peoples story's     " },
                    { letter: "C", text: "peoples stories     " },
                    { letter: "D", text: "people's story's    " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest20.png",
                alternatives: [
                    { letter: "A", text: "had survived           " },
                    { letter: "B", text: "survived      " },
                    { letter: "C", text: "would survive      " },
                    { letter: "D", text: "survives    " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest21.png",
                alternatives: [
                    { letter: "A", text: "ran—fast—during            " },
                    { letter: "B", text: "ran—fast during       " },
                    { letter: "C", text: "ran—fast, during       " },
                    { letter: "D", text: "ran—fast. During     " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest22.png",
                alternatives: [
                    { letter: "A", text: "are            " },
                    { letter: "B", text: "have been        " },
                    { letter: "C", text: "were       " },
                    { letter: "D", text: "is      " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest23.png",
                alternatives: [
                    { letter: "A", text: "sampler later,            " },
                    { letter: "B", text: "sampler;        " },
                    { letter: "C", text: "sampler,       " },
                    { letter: "D", text: "sampler, later,    " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/quest24.png",
                alternatives: [
                    { letter: "A", text: "Julian synthesized the alkaloid physostigmine in 1935; it             " },
                    { letter: "B", text: "in 1935 Julian synthesized the alkaloid physostigmine, which        " },
                    { letter: "C", text: "Julian's 1935 synthesis of the alkaloid physostigmine        " },
                    { letter: "D", text: "the alkaloid physostigmine was synthesized by Julian in 1935 and    " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/quest25.png",
                alternatives: [
                    { letter: "A", text: "species, both native and nonnative,             " },
                    { letter: "B", text: "species, both native and nonnative;        " },
                    { letter: "C", text: "species; both native and nonnative,"}, 
                    { letter: "D", text: "species both native and nonnative,     " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/quest26.png",
                alternatives: [
                    { letter: "A", text: "single-handedly, however;              " },
                    { letter: "B", text: "single-handedly; however,        " },
                    { letter: "C", text: "single-handedly, however,"}, 
                    { letter: "D", text: "single-handedly however      " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest27.png",
                alternatives: [
                    { letter: "A", text: "Instead,              " },
                    { letter: "B", text: "Likewise,        " },
                    { letter: "C", text: "Finally, "}, 
                    { letter: "D", text: "Additionally,      " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/quest28.png",
                alternatives: [
                    { letter: "A", text: "Secondly,               " },
                    { letter: "B", text: "Consequently,        " },
                    { letter: "C", text: "Moreover,  "}, 
                    { letter: "D", text: "However,     " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest29.png",
                alternatives: [
                    { letter: "A", text: "In addition, " },
                    { letter: "B", text: "Actually,  " },
                    { letter: "C", text: "However,"}, 
                    { letter: "D", text: "Regardless," }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest30.png",
                alternatives: [
                    { letter: "A", text: "Therefore,  " },
                    { letter: "B", text: "Alternately, " },
                    { letter: "C", text: "Nevertheless,"}, 
                    { letter: "D", text: "Likewise," }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/quest31.png",
                alternatives: [
                    { letter: "A", text: "To make batters rise, bakers use chemical leavening agents such as baking soda and baking powder.  " },
                    { letter: "B", text: "Baking soda and baking powder are chemical leavening agents that, when mixed with other ingredients, cause carbon dioxide to be released within a batter.  " },
                    { letter: "C", text: "Baking soda is pure sodium bicarbonate, and honey is a type of acidic ingredient."}, 
                    { letter: "D", text: " To produce carbon dioxide within a liquid batter, baking soda needs to be mixed with an acidic ingredient, whereas baking powder does not." }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest32.png",
                alternatives: [
                    { letter: "A", text: "Park's 2013 installation Unwoven Light, which included a chain-link fence and iridescent tiles made from plexiglass, featured light as its primary medium of expression.   " },
                    { letter: "B", text: "Korean American light artist Soo Sunny Park created Unwoven Light in 2013.  " },
                    { letter: "C", text: "The chain-link fence in Soo Sunny Park's Unwoven Light was fitted with tiles made from iridescent plexiglass. "}, 
                    { letter: "D", text: " In Unwoven Light, a 2013 work by Korean American artist Soo Sunny Park, light formed colorful prisms as it passed through a fence Park had fitted with iridescent tiles. " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/quest33.png",
                alternatives: [
                    { letter: "A", text: "Tan photographed Angkor Wat's plaster walls and then applied decorrelation stretch analysis to the photographs.    " },
                    { letter: "B", text: "Decorrelation stretch analysis is a novel digital imaging technique that Tan used to enhance the contrast between colors in a photograph.  " },
                    { letter: "C", text: "Using a novel digital imaging technique, Tan revealed hundreds of images hidden on the walls of Angkor Wat, a Cambodian temple. "}, 
                    { letter: "D", text: "Built to honor a Hindu god before becoming a Buddhist temple, Cambodia's Angkor Wat concealed hundreds of images on its plaster walls.  " }
                ],
                correct: "C"
            }
        ];
        
        // Dados das questões do Módulo 2
        const questionsModule2 = [
            {
                image: "imagens/prova1/mod2_quest1.png",
                alternatives: [
                    { letter: "A", text: "produced " },
                    { letter: "B", text: "denied " },
                    { letter: "C", text: "worried" },
                    { letter: "D", text: "predicted" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/mod2_quest2.png",
                alternatives: [
                    { letter: "A", text: "conceptualize" },
                    { letter: "B", text: "neglect " },
                    { letter: "C", text: "illustrate" },
                    { letter: "D", text: "overcome " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/mod2_quest3.png",
                alternatives: [
                    { letter: "A", text: "selecting" },
                    { letter: "B", text: "inspecting " },
                    { letter: "C", text: "creating" },
                    { letter: "D", text: "deciding" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest4.png",
                alternatives: [
                    { letter: "A", text: "surpassed by" },
                    { letter: "B", text: "comparable to " },
                    { letter: "C", text: "independent of " },
                    { letter: "D", text: "obtained from " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest5.png",
                alternatives: [
                    { letter: "A", text: "infallible" },
                    { letter: "B", text: "atypical " },
                    { letter: "C", text: "lucrative" },
                    { letter: "D", text: "tedious " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest6.png",
                alternatives: [
                    { letter: "A", text: "validated" },
                    { letter: "B", text: "created " },
                    { letter: "C", text: "challenged " },
                    { letter: "D", text: "restored" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest7.png",
                alternatives: [
                    { letter: "A", text: "proponent of" },
                    { letter: "B", text: "supplement to " },
                    { letter: "C", text: "beneficiary of " },
                    { letter: "D", text: "distraction for" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest8.png",
                alternatives: [
                    { letter: "A", text: "reciprocate" },
                    { letter: "B", text: "annotate" },
                    { letter: "C", text: "buttress " },
                    { letter: "D", text: "disengage " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest9.png",
                alternatives: [
                    { letter: "A", text: "It introduces a physical feature of female cuckoos that is described later in the text." },
                    { letter: "B", text: "It describes the appearance of the cuckoo nests mentioned earlier in the text. " },
                    { letter: "C", text: "It offers a detail about how female cuckoos carry out the behavior discussed in the text.  " },
                    { letter: "D", text: "It explains how other birds react to the female cuckoo behavior discussed in the text " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest10.png",
                alternatives: [
                    { letter: "A", text: "They watched how each cat moved its ears and head. " },
                    { letter: "B", text: "They examined how each cat reacted to the voice of a stranger. " },
                    { letter: "C", text: "They studied how each cat physically interacted with its owner.   " },
                    { letter: "D", text: "They tracked how each cat moved around the room. " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest11.png",
                alternatives: [
                    { letter: "A", text: "The choy sum planted in the soil without coffee grounds were significantly taller at the end of the experiment than the choy sum planted in the mixture of soil and coffee grounds. " },
                    { letter: "B", text: "The choy sum grown in the soil without coffee grounds weighed significantly less at the end of the experiment than the choy sum grown in the mixture of soil and coffee grounds.  " },
                    { letter: "C", text: "The choy sum seeds planted in the soil without coffee grounds sprouted significantly later in the experiment than did the seeds planted in the mixture of soil and coffee grounds.   " },
                    { letter: "D", text: "Significantly fewer of the choy sum seeds planted in the soil without coffee grounds sprouted plants than did the seeds planted in the mixture of soil and coffee grounds.  " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest12.png",
                alternatives: [
                    { letter: "A", text: "I heard her murmur, 'I can't bear flowers on a table.' They had evidently been giving her intense pain, for she positively closed her eyes as I moved them away." },
                    { letter: "B", text: "While we waited she took out a little, gold powder-box with a mirror in the lid, shook the poor little puff as though she loathed it, and dabbed her lovely nose." },
                    { letter: "C", text: "I saw, after that, she couldn't stand this place a moment longer, and, indeed, she jumped up and turned away while I went through the vulgar act of paying for the tea." },
                    { letter: "D", text: "She didn't even take her gloves off. She lowered her eyes and drummed on the table. When a faint violin sounded she winced and bit her lip again. Silence." }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest13.png",
                alternatives: [
                    { letter: "A", text: "aligned closely with uncertainty about tax and public spending policy in 2005 but differed from uncertainty about tax and public spending policy by a large amount in 2009." },
                    { letter: "B", text: "was substantially lower than uncertainty about tax and public spending policy each year from 2005 to 2010." },
                    { letter: "C", text: "reached its highest level between 2005 and 2010 in the same year that uncertainty about trade policy and tax and public spending policy reached their lowest levels." },
                    { letter: "D", text: "was substantially lower than uncertainty about trade policy in 2005 and substantially higher than uncertainty about trade policy in 2010." }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/mod2_quest14.png",
                alternatives: [
                    { letter: "A", text: "On average, participants perceived commentators in the debate as more knowledgeable about the issue than commentators in the panel." },
                    { letter: "B", text: "On average, participants perceived commentators in the panel as more knowledgeable about the issue than the single commentator." },
                    { letter: "C", text: "On average, participants who watched the panel correctly answered more questions about the issue than those who watched the debate or the single commentator did." },
                    { letter: "D", text: "On average, participants who watched the single commentator correctly answered more questions about the issue than those who watched the debate did." }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest15.png",
                alternatives: [
                    { letter: "A", text: "says of himself, 'I am a man / more sinned against than sinning.'" },
                    { letter: "B", text: "says during a growing storm, 'This tempest will not give me leave to ponder / On things would hurt me more.'" },
                    { letter: "C", text: "says to himself while striking his head, 'Beat at this gate that let thy folly in / And thy dear judgement out!'" },
                    { letter: "D", text: "says of himself, 'I will do such things— / What they are yet, I know not; but they shall be / The terrors of the earth!'" }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest16.png",
                alternatives: [
                    { letter: "A", text: "many theatergoers and readers today are likely to find Shakespeare's history plays less engaging than the tragedies." },
                    { letter: "B", text: "some of Shakespeare's tragedies are more relevant to today's audiences than twentieth-century plays." },
                    { letter: "C", text: "Romeo and Juliet is the most thematically accessible of all Shakespeare's tragedies." },
                    { letter: "D", text: "experts in English history tend to prefer Shakespeare's history plays to his other works" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest17.png",
                alternatives: [
                    { letter: "A", text: "conditions of the terrains in the Rio Grande Valley and Mesa Verde had greater similarities in the past than they do today." },
                    { letter: "B", text: "some Ancestral Puebloans migrated to the Rio Grande Valley in the late 1200s and carried farming practices with them." },
                    { letter: "C", text: "Indigenous peoples living in the Rio Grande Valley primarily planted crops and did not cultivate turkeys before 1280." },
                    { letter: "D", text: "the Ancestral Puebloans of Mesa Verde likely adopted the farming practices of Indigenous peoples living in other regions." }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest18.png",
                alternatives: [
                    { letter: "A", text: "struggle to find valid data about the behavior of politicians who do not currently hold office." },
                    { letter: "B", text: "can only conduct valid studies with people who have previously held office rather than people who presently hold office." },
                    { letter: "C", text: "should select a control group of people who differ from office holders in several significant ways." },
                    { letter: "D", text: "will find it difficult to identify a group of people who can function as an appropriate control group for their studies." }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/mod2_quest19.png",
                alternatives: [
                    { letter: "A", text: "story's of the South Asian immigrants " },
                    { letter: "B", text: "story's of the South Asian immigrants" },
                    { letter: "C", text: "stories of the South Asian immigrants " },
                    { letter: "D", text: "stories' of the South Asian immigrant's " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest20.png",
                alternatives: [
                    { letter: "A", text: "of " },
                    { letter: "B", text: "of," },
                    { letter: "C", text: "of— " },
                    { letter: "D", text: "of: " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest21.png",
                alternatives: [
                    { letter: "A", text: "company,  " },
                    { letter: "B", text: "company that " },
                    { letter: "C", text: "company" },
                    { letter: "D", text: "company,that " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest22.png",
                alternatives: [
                    { letter: "A", text: " carbon-13, (13C)  " },
                    { letter: "B", text: " carbon-13 (13C)" },
                    { letter: "C", text: " carbon-13,(13C)," },
                    { letter: "D", text: " carbon-13 (13C), " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/mod2_quest23.png",
                alternatives: [
                    { letter: "A", text: " walls, with  " },
                    { letter: "B", text: " walls with " },
                    { letter: "C", text: " walls so with " },
                    { letter: "D", text: " walls. With  " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/mod2_quest24.png",
                alternatives: [
                    { letter: "A", text: " to forge  " },
                    { letter: "B", text: " forging  " },
                    { letter: "C", text: " forged " },
                    { letter: "D", text: " and forging " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest25.png",
                alternatives: [
                    { letter: "A", text: "the mitigation of both street flooding and the resulting pollution of nearby waterways has been achieved by bioswales." },
                    { letter: "B", text: " the bioswales have mitigated both street flooding and the resulting pollution of nearby waterways." },
                    { letter: "C", text: " the bioswales' mitigation of both street flooding and the resulting pollution of nearby waterways has been achieved." },
                    { letter: "D", text: "both street flooding and the resulting pollution of nearby waterways have been mitigated by bioswales." }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest26.png",
                alternatives: [
                    { letter: "A", text: "continents geological" },
                    { letter: "B", text: "continents: geological " },
                    { letter: "C", text: "continents; geological " },
                    { letter: "D", text: "continents. Geological" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/mod2_quest27.png",
                alternatives: [
                    { letter: "A", text: "Afterward, " },
                    { letter: "B", text: "Additionally, " },
                    { letter: "C", text: "Indeed," },
                    { letter: "D", text: "Similarly, " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest28.png",
                alternatives: [
                    { letter: "A", text: "Alternatively, " },
                    { letter: "B", text: "Specifically,  " },
                    { letter: "C", text: "For example," },
                    { letter: "D", text: "As a result,  " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest29.png",
                alternatives: [
                    { letter: "A", text: "The Gregorian calendar has 365 days, which is one day longer than the Hanke-Henry permanent calendar." },
                    { letter: "B", text: "Adopting the Hanke-Henry permanent calendar would help solve a problem with the Gregorian calendar." },
                    { letter: "C", text: "Designed so calendar dates would occur on the same day of the week each year, the Hanke-Henry calendar supports more predictable scheduling than does the Gregorian calendar." },
                    { letter: "D", text: "The Hanke-Henry permanent calendar was developed as an alternative to the Gregorian calendar, which is currently the most-used calendar in the world." }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest30.png",
                alternatives: [
                    { letter: "A", text: "Historian Bruce Johansen believes that the Great Law of Peace was very influential." },
                    { letter: "B", text: "The influence theory is supported by the fact that Benjamin Franklin and Thomas Jefferson both studied the Haudenosaunee Confederacy." },
                    { letter: "C", text: "The influence theory holds that the principles of the Great Law of Peace, a centuries-old agreement binding six Native nations in the northeastern US, influenced the US Constitution." },
                    { letter: "D", text: "Native people, including the members of the Haudenosaunee Confederacy, influenced the founding of the US in many different ways." }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest31.png",
                alternatives: [
                    { letter: "A", text: "At around 9,800°F which classifies it as a G star, the Sun is hotter than most but not all of the stars within 10 parsecs of it." },
                    { letter: "B", text: "Astronomer Todd Henry determined that the Sun, at around 9,800°F is a G star, and several other stars within a 10-parsec range are A or F stars." },
                    { letter: "C", text: "Of the 357 stars within ten parsecs of the Sun, 327 are classified as K or M stars, with surface temperatures under 8,900°F." },
                    { letter: "D", text: "While most of the stars within 10 parsecs of the Sun are classified as K, M, A, or F stars, the Sun is classified as a G star due to its surface temperature of 9,800°F." }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/mod2_quest32.png",
                alternatives: [
                    { letter: "A", text: "Cathryn Halverson's Faraway Women and the 'Atlantic Monthly' discusses female authors whose autobiographies appeared in the magazine in the early 1900s." },
                    { letter: "B", text: "A magazine called the Atlantic Monthly, referred to in Cathryn Halverson's book title, was first published in 1857." },
                    { letter: "C", text: "Faraway Women and the 'Atlantic Monthly' features contributors to the Atlantic Monthly, first published in 1857 as a magazine focusing on politics, art, and literature." },
                    { letter: "D", text: "An author discussed by Cathryn Halverson is Juanita Harrison, whose autobiography appeared in the Atlantic Monthly in the early 1900s." }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/mod2_quest33.png",
                alternatives: [
                    { letter: "A", text: "A magnificent frigatebird never dives into the water, instead using its hook-tipped bill to snatch prey from the surface." },
                    { letter: "B", text: "Neither of a magnificent frigatebird's two ways of acquiring food requires the bird to dive into the water." },
                    { letter: "C", text: "Of the magnificent frigatebird's two ways of acquiring food, only one is known as kleptoparasitism." },
                    { letter: "D", text: "In addition to snatching prey from the water with its hook-tipped bill, a magnificent frigatebird takes food from other birds by force." }
                ],
                correct: "B"
            }
        ];
        
        // Dados das questões do Módulo de Matemática
        const mathQuestions = [
            {
                image: "imagens/prova1/math_quest1.png",
                alternatives: [
                    { letter: "A", text: "25" },
                    { letter: "B", text: "39" },
                    { letter: "C", text: "48" },
                    { letter: "D", text: "50" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest2.png",
                alternatives: [
                    { letter: "A", text: "25%" },
                    { letter: "B", text: "50%" },
                    { letter: "C", text: "75%" },
                    { letter: "D", text: "225%" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest3.png",
                alternatives: [
                    { letter: "A", text: "6" },
                    { letter: "B", text: "30" },
                    { letter: "C", text: "450" },
                    { letter: "D", text: "900" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest4.png",
                alternatives: [
                    { letter: "A", text: "(3)(8)x=83" },
                    { letter: "B", text: "8x=83+3" },
                    { letter: "C", text: "3x+8=83" },
                    { letter: "D", text: "8x+3=83" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest5.png",
                alternatives: [
                    { letter: "A", text: "With each monthly deposit, the amount in Hana's bank account increased by $25." },
                    { letter: "B", text: "Before Hana made any monthly deposits, the amount in her bank account was $25." },
                    { letter: "C", text: "After 1 monthly deposit, the amount in Hana's bank account was $25. " },
                    { letter: "D", text: "Hana made a total of 25 monthly deposits. " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest6.png",
                type: "numeric",  // Indica que é uma questão numérica
                question: "",
                correct: 9  // Agora é um array com múltiplas respostas corretas
            },
            {
                image: "imagens/prova1/math_quest7.png",
                type: "numeric",  // Indica que é uma questão numérica
                question: "",
                correct: 10
            },
            {
                image: "imagens/prova1/math_quest8.png",
                alternatives: [
                    { letter: "A", text: "f(x)=3x+29" },
                    { letter: "B", text: "f(x)=29x+32" },
                    { letter: "C", text: "f(x)=35x+29 " },
                    { letter: "D", text: "f(x)=32x+35 " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest9.png",
                alternatives: [
                    { letter: "A", text: "18°" },
                    { letter: "B", text: "72°" },
                    { letter: "C", text: "82° " },
                    { letter: "D", text: "162°" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest10.png",
                alternatives: [
                    { letter: "A", text: "y= 0.9 + 9.4x " },
                    { letter: "B", text: "y= 0.9 − 9.4x" },
                    { letter: "C", text: "y= 9.4 + 0.9x " },
                    { letter: "D", text: "y= 9.4 − 0.9x " }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest11.png",
                alternatives: [
                    { letter: "A", text: "0 " },
                    { letter: "B", text: "5" },
                    { letter: "C", text: "40 " },
                    { letter: "D", text: "80" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest12.png",
                alternatives: [
                    { letter: "A", text: "y=−2x−8 " },
                    { letter: "B", text: "y x= −8" },
                    { letter: "C", text: "y=−x−8" },
                    { letter: "D", text: "y=2x−8" }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest13.png",
                type: "fraction",  // Indica que é uma questão com fração
                question: "",
                correct: 1/5
            },
            {
                image: "imagens/prova1/math_quest14.png",
                type: "numeric",  // Indica que é uma questão com fração
                question: "",
                correct: 80
            },
            {
                image: "imagens/prova1/math_quest15.png",
                alternatives: [
                    { letter: "A", text: "y=13x− 1/3" },
                    { letter: "B", text: "y=9x+10" },
                    { letter: "C", text: "y=-x/3+10" },
                    { letter: "D", text: "y=-x/3+13" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest16.png",
                alternatives: [
                    { letter: "A", text: "The value of the bank account is estimated to be approximately 5 dollars greater in 1962 than in 1957. " },
                    { letter: "B", text: "The value of the bank account is estimated to be approximately 243 dollars in 1962." },
                    { letter: "C", text: " The value, in dollars, of the bank account is estimated to be approximately 5 times greater in 1962 than in 1957. " },
                    { letter: "D", text: "The value of the bank account is estimated to increase by approximately 243 dollars every 5 years between 1957 and 1972." }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest17.png",
                alternatives: [
                    { letter: "A", text: "It must decrease by 24.5 units. " },
                    { letter: "B", text: "It must increase by 24.5 units. " },
                    { letter: "C", text: "It must decrease by 7 units. " },
                    { letter: "D", text: " It must increase by 7 units. " }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest18.png",
                alternatives: [
                    { letter: "A", text: "f (x) =(x+ 44)² " },
                    { letter: "B", text: " f(x)=(x+176)²" },
                    { letter: "C", text: "f(x)=(176x+44)² " },
                    { letter: "D", text: "f(x)=(176x+176)² " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest19.png",
                alternatives: [
                    { letter: "A", text: "w=√x/y -19 " },
                    { letter: "B", text: "w=√28x/14y -19" },
                    { letter: "C", text: "w= [x/y]² -19 " },
                    { letter: "D", text: "w= [28x/14y]² -19 " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest20.png",
                type: "numeric",  // Indica que é uma questão com fração
                question: "",
                correct: 100
            },
            {
                image: "imagens/prova1/math_quest21.png",
                type: "fraction",  // Indica que é uma questão com fração
                question: "",
                correct: 361/8
            },
            {
                image: "imagens/prova1/math_quest22.png",
                alternatives: [
                    { letter: "A", text: "8√2+√80 " },
                    { letter: "B", text: "12" },
                    { letter: "C", text: "24√80" },
                    { letter: "D", text: "24" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest23.png",
                alternatives: [
                    { letter: "A", text: "b/h " },
                    { letter: "B", text: "b/k" },
                    { letter: "C", text: "45/h" },
                    { letter: "D", text: "45/k" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest24.png",
                alternatives: [
                    { letter: "A", text: "-8 " },
                    { letter: "B", text: "-6" },
                    { letter: "C", text: "6" },
                    { letter: "D", text: "8" }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest25.png",
                alternatives: [
                    { letter: "A", text: "29√2 " },
                    { letter: "B", text: "58√2" },
                    { letter: "C", text: "58+58√2" },
                    { letter: "D", text: "58+116√2" }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest26.png",
                alternatives: [
                    { letter: "A", text: "-13 " },
                    { letter: "B", text: "-19" },
                    { letter: "C", text: "-14" },
                    { letter: "D", text: "-12" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest27.png",
                type: "numeric",  
                question: "",
                correct: 5
            }
        ];
        
        // Dados das questões do Segundo Módulo de Matemática
        const mathQuestionsModule2 = [
             {
                image: "imagens/prova1/math_quest28.png",
                alternatives: [
                    { letter: "A", text: "1989" },
                    { letter: "B", text: "1994" },
                    { letter: "C", text: "1995" },
                    { letter: "D", text: "1998" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest29.png",
                alternatives: [
                    { letter: "A", text: "0.3" },
                    { letter: "B", text: "2.9" },
                    { letter: "C", text: "3.344" },
                    { letter: "D", text: "6.864" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest30.png",
                alternatives: [
                    { letter: "A", text: "7x⁶" },
                    { letter: "B", text: "17x³" },
                    { letter: "C", text: "7x³" },
                    { letter: "D", text: "17x⁶" }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest31.png",
                alternatives: [
                    { letter: "A", text: "(15,3)" },
                    { letter: "B", text: "(16,2)" },
                    { letter: "C", text: "(17,1)" },
                    { letter: "D", text: "(18,0)" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest32.png",
                alternatives: [
                    { letter: "A", text: "x>0 y>0" },
                    { letter: "B", text: "x>0  y<0" },
                    { letter: "C", text: "x<0 y>0" },
                    { letter: "D", text: "x<0 y<0" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest33.png",
                type: "numeric",  // Indica que é uma questão numérica
                question: "",
                correct: [15,-5]  // Agora é um array com múltiplas respostas corretas
            },
            {
                image: "imagens/prova1/math_quest34.png",
                type: "numeric",  // Indica que é uma questão numérica
                question: "",
                correct: 50  
            },
            {
                image: "imagens/prova1/math_quest35.png",
                alternatives: [
                    { letter: "A", text: "", image: "imagens/prova1/math_quest35_a.png" },
                    { letter: "B", text: "", image: "imagens/prova1/math_quest35_b.png" },
                    { letter: "C", text: "", image: "imagens/prova1/math_quest35_c.png" },
                    { letter: "D", text: "", image: "imagens/prova1/math_quest35_d.png" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest36.png",
                alternatives: [
                    { letter: "A", text: "0" },
                    { letter: "B", text: "1" },
                    { letter: "C", text: "27" },
                    { letter: "D", text: "270" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest37.png",
                alternatives: [
                    { letter: "A", text: "It is plausible that the proportion is between 0.45 and 0.53. " },
                    { letter: "B", text: "It is plausible that the proportion is less than 0.45. " },
                    { letter: "C", text: "The proportion is exactly 0.49. " },
                    { letter: "D", text: "It is plausible that the proportion is greater than 0.53. " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest38.png",
                alternatives: [
                    { letter: "A", text: "34" },
                    { letter: "B", text: "35" },
                    { letter: "C", text: "38" },
                    { letter: "D", text: "39" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest39.png",
                alternatives: [
                    { letter: "A", text: "7/4" },
                    { letter: "B", text: "9/4" },
                    { letter: "C", text: "4" },
                    { letter: "D", text: "7" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest40.png",
                type: "fraction",  // Indica que é uma questão com fração
                question: "",
                correct: 3/10
            },
            {
                image: "imagens/prova1/math_quest41.png",
                type: "numeric",  // Indica que é uma questão numérica
                question: "",
                correct: 2  
            },
            {
                image: "imagens/prova1/math_quest42.png",
                alternatives: [
                    { letter: "A", text: "7,500" },
                    { letter: "B", text: "15,000" },
                    { letter: "C", text: "22,500" },
                    { letter: "D", text: "45,000" }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest43.png",
                alternatives: [
                    { letter: "A", text: "3" },
                    { letter: "B", text: "21" },
                    { letter: "C", text: "41" },
                    { letter: "D", text: "139" }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest44.png",
                alternatives: [
                    { letter: "A", text: "0" },
                    { letter: "B", text: "1/7" },
                    { letter: "C", text: "4/3" },
                    { letter: "D", text: "4" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest45.png",
                alternatives: [
                    { letter: "A", text: "-130" },
                    { letter: "B", text: "-13" },
                    { letter: "C", text: "-23/2" },
                    { letter: "D", text: "-3/2" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest46.png",
                alternatives: [
                    { letter: "A", text: "The metal ball's minimum height was 3 inches above the ground. " },
                    { letter: "B", text: "The metal ball's minimum height was 7 inches above the ground. " },
                    { letter: "C", text: "The metal ball's height was 3 inches above the ground when it started moving." },
                    { letter: "D", text: "The metal ball's height was 7 inches above the ground when it started moving." }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest47.png",
                type: "fraction",  // Indica que é uma questão com fração
                question: "",
                correct: 15/17
            },
            {
                image: "imagens/prova1/math_quest48.png",
                type: "numeric",  // Indica que é uma questão numérica
                question: "",
                correct: 51  
            },
            {
                image: "imagens/prova1/math_quest49.png",
                alternatives: [
                    { letter: "A", text: "zero" },
                    { letter: "B", text: "Exactly one" },
                    { letter: "C", text: "Exactly two " },
                    { letter: "D", text: "Infinitely many " }
                ],
                correct: "A"
            },
            {
                image: "imagens/prova1/math_quest50.png",
                alternatives: [
                    { letter: "A", text: "4" },
                    { letter: "B", text: "5" },
                    { letter: "C", text: "36" },
                    { letter: "D", text: "64" }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest51.png",
                alternatives: [
                    { letter: "A", text: "The median of data set B is equal to the median of data set A, and the range of data set B is equal to the range of data set A. " },
                    { letter: "B", text: "The median of data set B is equal to the median of data set A, and the range of data set B is greater than the range of data set A." },
                    { letter: "C", text: "The median of data set B is greater than the median of data set A, and the range of data set B is equal to the range of data set A. " },
                    { letter: "D", text: "The median of data set B is greater than the median of data set A, and the range of data set B is greater than the range of data set A. " }
                ],
                correct: "C"
            },
            {
                image: "imagens/prova1/math_quest52.png",
                alternatives: [
                    { letter: "A", text: " (x −2)² +(y −1)² =49" },
                    { letter: "B", text: " x²+(y −3)² =49" },
                    { letter: "C", text: "(x +2)² +(y −1)² =49" },
                    { letter: "D", text: "x²+(y+1)²=49" }
                ],
                correct: "D"
            },
            {
                image: "imagens/prova1/math_quest53.png",
                alternatives: [
                    { letter: "A", text: " 4" },
                    { letter: "B", text: " 8" },
                    { letter: "C", text: "9" },
                    { letter: "D", text: "16" }
                ],
                correct: "B"
            },
            {
                image: "imagens/prova1/math_quest54.png",
                type: "numeric",  // Indica que é uma questão numérica
                question: "",
                correct: 600  
            }
        ];
        
        // Variáveis de controle
        let currentModule = 1;
        let currentQuestionIndex = 0;
        let scoreModule1 = 0;   // Módulo 1: Reading & Writing
        let scoreModule2 = 0;   // Módulo 2: Reading & Writing
        let scoreMathModule1 = 0; // Módulo 3: Math
        let scoreMathModule2 = 0; // Módulo 4: Math
        let selectedAlternative = null;
        let mathModuleCompleted = false; // Nova variável para controlar se o primeiro módulo de matemática foi concluído
        
        // Elementos do DOM
        const questionImage = document.getElementById('question-image');
        const alternativesContainer = document.getElementById('alternatives-container');
        const feedback = document.getElementById('feedback');
        const nextButton = document.getElementById('next-button');
        const currentQuestionSpan = document.getElementById('current-question');
        const totalQuestionsSpan = document.getElementById('total-questions');
        const moduleIndicator = document.getElementById('module-indicator');
        const questionSection = document.getElementById('question-section');
        const transitionSection = document.getElementById('transition-section');
        const mathTransitionSection = document.getElementById('math-transition-section');
        const math2TransitionSection = document.getElementById('math2-transition-section');
        const resultsSection = document.getElementById('results-section');
        const rwScoreElement = document.getElementById('rw-score');
        const mathScoreElement = document.getElementById('math-score');
        const totalScoreElement = document.getElementById('total-score');
        const resultsMessage = document.getElementById('results-message');
        const restartButton = document.getElementById('restart-button');
        const startModule2Button = document.getElementById('start-module2-button');
        const startMathButton = document.getElementById('start-math-button');
        const startMath2Button = document.getElementById('start-math2-button');
        
        // Inicialização
        totalQuestionsSpan.textContent = questionsModule1.length;
        loadQuestion();
        
        // Função para converter fração em número decimal
        function fractionToDecimal(fraction) {
            // Verifica se é uma fração no formato a/b
            const fractionMatch = fraction.match(/^(\d+)\/(\d+)$/);
            if (fractionMatch) {
                const numerator = parseInt(fractionMatch[1]);
                const denominator = parseInt(fractionMatch[2]);
                return numerator / denominator;
            }
            
            // Se não for fração, tenta converter para número
            const number = parseFloat(fraction);
            return isNaN(number) ? null : number;
        }
        
        // Carregar questão atual
        function loadQuestion() {
            let questions;
            let moduleName;
            
            if (currentModule === 1) {
                questions = questionsModule1;
                moduleName = "Módulo 1";
            } else if (currentModule === 2) {
                questions = questionsModule2;
                moduleName = "Módulo 2";
            } else if (currentModule === 3) {
                questions = mathQuestions;
                moduleName = "Matemática";
            } else if (currentModule === 4) {
                questions = mathQuestionsModule2;
                moduleName = "Matemática 2";
            }
            
            const question = questions[currentQuestionIndex];
            questionImage.src = question.image;
            currentQuestionSpan.textContent = currentQuestionIndex + 1;
            totalQuestionsSpan.textContent = questions.length;
            moduleIndicator.textContent = moduleName;
            
            // Limpar alternativas anteriores
            alternativesContainer.innerHTML = '';
            feedback.className = 'feedback';
            feedback.textContent = '';
            nextButton.classList.remove('show');
            selectedAlternative = null;
            
            // Verificar se é uma questão numérica
            if (question.type === "numeric") {
                // Criar campo de entrada numérica
                const numericContainer = document.createElement('div');
                numericContainer.className = 'numeric-container';
                
                const questionText = document.createElement('p');
                questionText.className = 'numeric-question';
                questionText.textContent = question.question;
                
                const inputField = document.createElement('input');
                inputField.type = 'number';
                inputField.id = 'numeric-answer';
                inputField.className = 'numeric-input';
                
                const submitButton = document.createElement('button');
                submitButton.textContent = 'Enviar Resposta';
                submitButton.className = 'submit-button';
                
                submitButton.addEventListener('click', () => {
                    const userAnswer = parseInt(inputField.value);
                    checkNumericAnswer(userAnswer, question.correct);
                });
                
                numericContainer.appendChild(questionText);
                numericContainer.appendChild(inputField);
                numericContainer.appendChild(submitButton);
                alternativesContainer.appendChild(numericContainer);
            } else if (question.type === "fraction") {
                // Criar campo de entrada para frações
                const fractionContainer = document.createElement('div');
                fractionContainer.className = 'numeric-container';
                
                const questionText = document.createElement('p');
                questionText.className = 'numeric-question';
                questionText.textContent = question.question;
                
                const inputField = document.createElement('input');
                inputField.type = 'text';
                inputField.id = 'fraction-answer';
                inputField.className = 'numeric-input';
                inputField.placeholder = 'Ex: 72, 72.0, 72/1, 144/2';
                
                const hint = document.createElement('p');
                hint.className = 'input-hint';
                hint.textContent = 'Você pode inserir números decimais ou frações (ex: 1/3)';
                
                const submitButton = document.createElement('button');
                submitButton.textContent = 'Enviar Resposta';
                submitButton.className = 'submit-button';
                
                submitButton.addEventListener('click', () => {
                    const userAnswer = inputField.value.trim();
                    checkFractionAnswer(userAnswer, question.correct);
                });
                
                fractionContainer.appendChild(questionText);
                fractionContainer.appendChild(inputField);
                fractionContainer.appendChild(hint);
                fractionContainer.appendChild(submitButton);
                alternativesContainer.appendChild(fractionContainer);
            } else {
                // Criar alternativas para questões de múltipla escolha
                question.alternatives.forEach(alt => {
                    const alternativeElement = document.createElement('div');
                    alternativeElement.className = 'alternative';
                    alternativeElement.dataset.letter = alt.letter;
                    
                    // Criar a estrutura da alternativa
                    const letterElement = document.createElement('div');
                    letterElement.className = 'alternative-letter';
                    letterElement.textContent = alt.letter;
                    
                    alternativeElement.appendChild(letterElement);
                    
                    // Verificar se há uma imagem para esta alternativa
                    if (alt.image) {
                        const imageElement = document.createElement('img');
                        imageElement.src = alt.image;
                        imageElement.alt = `Alternativa ${alt.letter}`;
                        imageElement.className = 'alternative-image';
                        alternativeElement.appendChild(imageElement);
                    }
                    
                    // Adicionar o texto da alternativa
                    const textElement = document.createElement('div');
                    textElement.className = 'alternative-text';
                    textElement.textContent = alt.text;
                    alternativeElement.appendChild(textElement);
                    
                    alternativeElement.addEventListener('click', () => selectAlternative(alt.letter));
                    alternativesContainer.appendChild(alternativeElement);
                });
            }
        }
        
        // Verificar resposta numérica - MODIFICADA PARA ACEITAR MÚLTIPLAS RESPOSTAS
        function checkNumericAnswer(userAnswer, correctAnswers) {
            let isCorrect = false;
            
            // Verificar se correctAnswers é um array (múltiplas respostas)
            if (Array.isArray(correctAnswers)) {
                // Verificar se a resposta do usuário está no array de respostas corretas
                isCorrect = correctAnswers.includes(userAnswer);
            } else {
                // Se não for um array, comparar diretamente
                isCorrect = userAnswer === correctAnswers;
            }
            
            if (isCorrect) {
                // Incrementar o score do módulo atual
                if (currentModule === 1) {
                    scoreModule1++;
                } else if (currentModule === 2) {
                    scoreModule2++;
                } else if (currentModule === 3) {
                    scoreMathModule1++;
                } else if (currentModule === 4) {
                    scoreMathModule2++;
                }
                
                feedback.textContent = 'Resposta Correta!';
                feedback.className = 'feedback correct show';
            } else {
                // Se for um array, mostrar todas as respostas corretas
                if (Array.isArray(correctAnswers)) {
                    feedback.textContent = `Resposta Incorreta! As respostas corretas são: ${correctAnswers.join(', ')}`;
                } else {
                    feedback.textContent = `Resposta Incorreta! A resposta correta é: ${correctAnswers}`;
                }
                feedback.className = 'feedback incorrect show';
            }
            
            // Mostrar botão de próxima questão
            nextButton.classList.add('show');
            
            // Avançar automaticamente após 2 segundos
            setTimeout(() => {
                nextQuestion();
            }, 2000);
        }
        
        // Verificar resposta com fração
        function checkFractionAnswer(userAnswer, correctAnswer) {
            const decimalValue = fractionToDecimal(userAnswer);
            
            if (decimalValue === null) {
                feedback.textContent = 'Formato inválido! Por favor, insira um número ou fração válida.';
                feedback.className = 'feedback incorrect show';
                return;
            }
            
            // Verificar se a resposta está correta (com tolerância para arredondamento)
            const isCorrect = Math.abs(decimalValue - correctAnswer) < 0.0001;
            
            if (isCorrect) {
                // Incrementar o score do módulo atual
                if (currentModule === 1) {
                    scoreModule1++;
                } else if (currentModule === 2) {
                    scoreModule2++;
                } else if (currentModule === 3) {
                    scoreMathModule1++;
                } else if (currentModule === 4) {
                    scoreMathModule2++;
                }
                
                feedback.textContent = 'Resposta Correta!';
                feedback.className = 'feedback correct show';
            } else {
                feedback.textContent = `Resposta Incorreta! A resposta correta é: ${correctAnswer}`;
                feedback.className = 'feedback incorrect show';
            }
            
            // Mostrar botão de próxima questão
            nextButton.classList.add('show');
            
            // Avançar automaticamente após 2 segundos
            setTimeout(() => {
                nextQuestion();
            }, 2000);
        }
        
        // Selecionar alternativa
        function selectAlternative(letter) {
            // Remover seleção anterior
            document.querySelectorAll('.alternative').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Marcar alternativa selecionada
            const selectedElement = document.querySelector(`.alternative[data-letter="${letter}"]`);
            selectedElement.classList.add('selected');
            selectedAlternative = letter;
            
            // Verificar resposta
            let questions;
            if (currentModule === 1) {
                questions = questionsModule1;
            } else if (currentModule === 2) {
                questions = questionsModule2;
            } else if (currentModule === 3) {
                questions = mathQuestions;
            } else if (currentModule === 4) {
                questions = mathQuestionsModule2;
            }
            
            const question = questions[currentQuestionIndex];
            const isCorrect = letter === question.correct;
            
            if (isCorrect) {
                // Incrementar o score do módulo atual
                if (currentModule === 1) {
                    scoreModule1++;
                } else if (currentModule === 2) {
                    scoreModule2++;
                } else if (currentModule === 3) {
                    scoreMathModule1++;
                } else if (currentModule === 4) {
                    scoreMathModule2++;
                }
                
                feedback.textContent = 'Resposta Correta!';
                feedback.className = 'feedback correct show';
            } else {
                feedback.textContent = `Resposta Incorreta! A resposta correta é: ${question.correct}`;
                feedback.className = 'feedback incorrect show';
            }
            
            // Mostrar botão de próxima questão
            nextButton.classList.add('show');
            
            // Avançar automaticamente após 2 segundos
            setTimeout(() => {
                nextQuestion();
            }, 2000);
        }
        
        // Próxima questão
        function nextQuestion() {
            let questions;
            if (currentModule === 1) {
                questions = questionsModule1;
            } else if (currentModule === 2) {
                questions = questionsModule2;
            } else if (currentModule === 3) {
                questions = mathQuestions;
            } else if (currentModule === 4) {
                questions = mathQuestionsModule2;
            }
            
            currentQuestionIndex++;
            
            if (currentQuestionIndex < questions.length) {
                loadQuestion();
            } else {
                if (currentModule === 1) {
                    // Mostrar tela de transição para o Módulo 2
                    showTransition();
                } else if (currentModule === 2) {
                    // Mostrar tela de transição para o Módulo de Matemática
                    showMathTransition();
                } else if (currentModule === 3) {
                    // Mostrar tela de transição para o Segundo Módulo de Matemática
                    showMath2Transition();
                } else {
                    // Mostrar resultados finais
                    showResults();
                }
            }
        }
        
        // Mostrar tela de transição
        function showTransition() {
            questionSection.style.display = 'none';
            transitionSection.style.display = 'block';
        }
        
        // Mostrar tela de transição para matemática
        function showMathTransition() {
            questionSection.style.display = 'none';
            mathTransitionSection.style.display = 'block';
        }
        
        // Mostrar tela de transição para o segundo módulo de matemática
        function showMath2Transition() {
            questionSection.style.display = 'none';
            math2TransitionSection.style.display = 'block';
        }
        
        // Iniciar Módulo 2
        startModule2Button.addEventListener('click', () => {
            currentModule = 2;
            currentQuestionIndex = 0;
            transitionSection.style.display = 'none';
            questionSection.style.display = 'block';
            loadQuestion();
        });
        
        // Iniciar Módulo de Matemática
        startMathButton.addEventListener('click', () => {
            currentModule = 3;
            currentQuestionIndex = 0;
            mathTransitionSection.style.display = 'none';
            questionSection.style.display = 'block';
            loadQuestion();
        });
        
        // Iniciar Segundo Módulo de Matemática
        startMath2Button.addEventListener('click', () => {
            currentModule = 4;
            currentQuestionIndex = 0;
            math2TransitionSection.style.display = 'none';
            questionSection.style.display = 'block';
            loadQuestion();
        });
        
        // Função para calcular pontuação SAT com base no número de acertos
        function calculateSATScore(correct, total, minScore = 200, maxScore = 800) {
            // Fórmula de conversão aproximada baseada no site de referência
            // Esta é uma simplificação da tabela de conversão real do SAT
            const percentage = correct / total;
            // Calcular a pontuação na escala SAT (minScore a maxScore)
            const score = Math.round(minScore + (percentage * (maxScore - minScore)));
            return score;
        }
        
        // Mostrar resultados
        function showResults() {
            questionSection.style.display = 'none';
            resultsSection.style.display = 'block';
            
            // Calcular acertos por seção
            const rwCorrect = scoreModule1 + scoreModule2;
            const mathCorrect = scoreMathModule1 + scoreMathModule2;
            
            // Total de questões por seção
            const rwTotal = questionsModule1.length + questionsModule2.length;
            const mathTotal = mathQuestions.length + mathQuestionsModule2.length;
            
            // Calcular pontuações SAT
            const rwScore = calculateSATScore(rwCorrect, rwTotal, 200, 800);
            const mathScore = calculateSATScore(mathCorrect, mathTotal, 200, 800);
            const totalScore = rwScore + mathScore;
            
            // Atualizar as pontuações na tela
            rwScoreElement.textContent = rwScore;
            mathScoreElement.textContent = mathScore;
            totalScoreElement.textContent = totalScore;
            
            // Animar as barras de progresso
            setTimeout(() => {
                // Calcular a porcentagem para a barra de progresso (considerando o mínimo de 200)
                const rwPercentage = ((rwScore - 200) / 600) * 100;
                const mathPercentage = ((mathScore - 200) / 600) * 100;
                
                document.getElementById('rw-score-bar').style.width = `${rwPercentage}%`;
                document.getElementById('math-score-bar').style.width = `${mathPercentage}%`;
            }, 300);
            
            // Mensagem baseada na pontuação total
            let message = '';
            const percentage = (totalScore / 1600) * 100;
            
            if (percentage >= 90) {
                message = 'Excelente! Você está entre os melhores!';
            } else if (percentage >= 70) {
                message = 'Muito bom! Você foi muito bem!';
            } else if (percentage >= 50) {
                message = 'Bom trabalho! Continue estudando!';
            } else {
                message = 'Não desista! Tente novamente!';
            }
            
            resultsMessage.textContent = message;
        }
        
        // Reiniciar simulado
        restartButton.addEventListener('click', () => {
            currentModule = 1;
            currentQuestionIndex = 0;
            scoreModule1 = 0;
            scoreModule2 = 0;
            scoreMathModule1 = 0;
            scoreMathModule2 = 0;
            selectedAlternative = null;
            mathModuleCompleted = false;
            
            questionSection.style.display = 'block';
            resultsSection.style.display = 'none';
            transitionSection.style.display = 'none';
            mathTransitionSection.style.display = 'none';
            math2TransitionSection.style.display = 'none';
            
            loadQuestion();
        });
        
        // Botão de próxima questão (caso o usuário queira avançar antes do tempo)
        nextButton.addEventListener('click', () => {
            nextQuestion();
        });
    </script>
</body>
</html>