<?php
class DbSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function open(string $savePath, string $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $sessionId): string|false {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['data'];
        }
        return '';
    }

    public function write(string $sessionId, string $data): bool {
        $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (session_id, user_id, data) VALUES (?, ?, ?)");
        $stmt->execute([$sessionId, $userId, $data]);
        return true;
    }

    public function destroy(string $sessionId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        return true;
    }

    public function gc(int $maxlifetime): int|false {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_accessed < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$maxlifetime]);
        return true;
    }

    public function destroyUserSessions($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return true;
    }
}
