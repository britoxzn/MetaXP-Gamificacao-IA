document.addEventListener('DOMContentLoaded', function() {
    // Verifica se estamos na página de metas
    if (!document.querySelector('.goal-card')) return;

    // Sistema de arrastar para completar tarefas (com feedback de progresso)
    const goalCards = document.querySelectorAll('.goal-card');
    let completionZone;

    // Criação da zona de conclusão com o ícone de "meta concluída"
    if (goalCards.length > 0) {
        completionZone = document.createElement('div');
        completionZone.className = 'completion-zone';
        completionZone.innerHTML = '<i class="fas fa-check-circle"></i> Solte aqui para marcar como concluído';
        document.body.appendChild(completionZone);

        // Configura eventos de drag-and-drop para os cards
        setupDragAndDrop();
    }

    // Atualização em tempo real do progresso de XP
    if (document.querySelector('.xp-progress-bar')) {
        updateXpProgress(); // Executa imediatamente
        const xpInterval = setInterval(updateXpProgress, 30000);
        
        // Limpa o intervalo quando a página é fechada/navegada
        window.addEventListener('beforeunload', () => {
            clearInterval(xpInterval);
        });
    }

    // Integração com IA (botão que sugere dicas de produtividade)
    const aiButton = document.querySelector('.btn-ai');
    if (aiButton) {
        aiButton.addEventListener('click', fetchAiSuggestion);
    }

    // Funções auxiliares
    function setupDragAndDrop() {
        // Eventos para os cards de metas
        goalCards.forEach(card => {
            card.setAttribute('draggable', 'true');
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
        });

        // Eventos para a zona de conclusão
        completionZone.addEventListener('dragover', handleDragOver);
        completionZone.addEventListener('dragenter', handleDragEnter);
        completionZone.addEventListener('dragleave', handleDragLeave);
        completionZone.addEventListener('drop', handleDrop);
    }

    function handleDragStart(e) {
        this.classList.add('dragging');
        e.dataTransfer.setData('text/plain', this.dataset.id);
        e.dataTransfer.effectAllowed = 'move';
        
        // Esconde o card original durante o arrasto
        setTimeout(() => this.style.visibility = 'hidden', 0);
    }

    function handleDragEnd() {
        this.classList.remove('dragging');
        this.style.visibility = 'visible';
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }

    function handleDragLeave() {
        this.classList.remove('drag-over');
    }

    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const goalId = e.dataTransfer.getData('text/plain');
        const draggedCard = document.querySelector(`.goal-card[data-id="${goalId}"]`);
        
        if (draggedCard) {
            // Marca a meta como concluída
            completeGoal(goalId, draggedCard);
        }
    }

    async function completeGoal(goalId, cardElement) {
        try {
            const response = await fetch('api/complete_goal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: goalId })
            });

            if (!response.ok) {
                throw new Error('Falha ao concluir a meta');
            }

            const data = await response.json();
            
            if (data.success) {
                // Animação de conclusão (feedback visual)
                cardElement.classList.add('completed');
                setTimeout(() => {
                    cardElement.remove();
                    updateXpProgress(); // Atualiza o XP imediatamente
                }, 500);
                
                // Feedback visual de conclusão
                showCompletionFeedback();
            }
        } catch (error) {
            console.error('Erro:', error);
            cardElement.style.visibility = 'visible';
        }
    }

    function showCompletionFeedback() {
        const feedback = document.createElement('div');
        feedback.className = 'completion-feedback';
        feedback.innerHTML = '<i class="fas fa-check"></i> Parabéns! Meta concluída! +100 XP';
        document.body.appendChild(feedback);
        
        setTimeout(() => {
            feedback.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            feedback.classList.remove('show');
            setTimeout(() => feedback.remove(), 500);
        }, 3000);
    }

    async function updateXpProgress() {
        try {
            const response = await fetch('api/get_xp.php');
            
            if (!response.ok) {
                throw new Error('Falha ao carregar progresso de XP');
            }
            
            const data = await response.json();
            
            // Atualiza a UI com o XP e nível atual
            const xpElement = document.querySelector('.xp-value');
            const levelElement = document.querySelector('.level-display');
            const progressBar = document.querySelector('.xp-progress-bar');
            
            if (xpElement) xpElement.textContent = data.xp;
            if (levelElement) levelElement.textContent = `Nível ${data.level}`;
            
            if (progressBar) {
                const progressPercentage = (data.xp % 1000) / 10;
                progressBar.style.width = `${progressPercentage}%`;
                progressBar.setAttribute('aria-valuenow', progressPercentage);
            }
        } catch (error) {
            console.error('Erro ao atualizar XP:', error);
        }
    }

    async function fetchAiSuggestion() {
        const aiCard = document.querySelector('.ai-card');
        if (!aiCard) return;
        
        const button = this;
        const originalText = button.innerHTML;
        
        try {
            // Feedback visual enquanto carrega
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Pensando...';
            
            const response = await fetch('api/ai_suggestion.php');
            
            if (!response.ok) {
                throw new Error('Falha ao obter sugestão');
            }
            
            const data = await response.json();
            
            // Atualiza o card com a sugestão
            const suggestionText = aiCard.querySelector('p') || document.createElement('p');
            suggestionText.textContent = `"${data.suggestion}"`;
            
            if (!aiCard.contains(suggestionText)) {
                aiCard.appendChild(suggestionText);
            }
            
            // Animação de nova sugestão
            aiCard.classList.add('new-suggestion');
            setTimeout(() => aiCard.classList.remove('new-suggestion'), 1000);
            
        } catch (error) {
            console.error('Erro:', error);
            aiCard.querySelector('p').textContent = "Não foi possível obter uma sugestão no momento.";
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }
});
