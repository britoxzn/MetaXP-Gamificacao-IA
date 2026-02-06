<?php
class MetaXP_GoalTracker {
    // Atributos
    private $goalName;
    private $goalDescription;
    private $goalDeadline;
    private $goalProgress;

    // Construtor
    public function __construct($name, $description, $deadline) {
        $this->goalName = $name;
        $this->goalDescription = $description;
        $this->goalDeadline = $deadline;
        $this->goalProgress = 0;  // Começa com progresso 0%
    }

    // Método para atualizar o progresso
    public function updateProgress($newProgress) {
        if ($newProgress >= 0 && $newProgress <= 100) {
            $this->goalProgress = $newProgress;
        } else {
            echo "Progresso deve estar entre 0 e 100.\n";
        }
    }

    // Método para marcar o objetivo como concluído
    public function markAsCompleted() {
        $this->goalProgress = 100;
    }

    // Método para exibir informações do objetivo
    public function displayGoalInfo() {
        echo "Objetivo: " . $this->goalName . "\n";
        echo "Descrição: " . $this->goalDescription . "\n";
        echo "Prazo: " . $this->goalDeadline . "\n";
        echo "Progresso: " . $this->goalProgress . "%\n";
    }

    // Método para aplicar gamificação (Exemplo: "Badges" para metas alcançadas)
    public function applyGamification() {
        if ($this->goalProgress == 100) {
            echo "Parabéns! Você alcançou a meta e ganhou um Badget de Conquista.\n";
        } elseif ($this->goalProgress >= 50) {
            echo "Ótimo progresso! Você está a meio caminho de conquistar a meta.\n";
        } else {
            echo "Continue, você está no caminho certo.\n";
        }
    }
}

// Exemplo de uso:
$goal1 = new MetaXP_GoalTracker("Ler um livro", "Ler um livro de 300 páginas até o final do mês.", "30/04/2025");
$goal1->displayGoalInfo();  // Exibe as informações do objetivo
$goal1->updateProgress(60);  // Atualiza o progresso para 60%
$goal1->applyGamification();  // Aplica gamificação baseada no progresso
$goal1->displayGoalInfo();  // Exibe as informações atualizadas
?>
