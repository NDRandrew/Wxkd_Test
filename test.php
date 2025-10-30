<?php
@session_start();

require_once '../permissions_config.php';

function json_encode_custom($data) {
    if (is_null($data)) return 'null';
    if ($data === true) return 'true';
    if ($data === false) return 'false';
    if (is_int($data) || is_float($data)) return (string)$data;
    
    if (is_string($data)) {
        $data = str_replace(['\\', '"', "\r", "\n", "\t"], ['\\\\', '\\"', '\\r', '\\n', '\\t'], $data);
        $data = preg_replace('/[\x00-\x1F\x7F]/', '', $data);
        return '"' . $data . '"';
    }
    
    if (is_array($data)) {
        $isAssoc = array_keys($data) !== range(0, count($data) - 1);
        $items = [];
        foreach ($data as $key => $value) {
            $encodedValue = json_encode_custom($value);
            $items[] = $isAssoc ? '"' . addslashes($key) . '":' . $encodedValue : $encodedValue;
        }
        return $isAssoc ? '{' . implode(',', $items) . '}' : '[' . implode(',', $items) . ']';
    }
    
    if (is_object($data)) {
        $items = [];
        foreach (get_object_vars($data) as $key => $value) {
            $items[] = '"' . addslashes($key) . '":' . json_encode_custom($value);
        }
        return '{' . implode(',', $items) . '}';
    }
    
    return 'null';
}

function formatMessagesForJson($messages) {
    if (!is_array($messages)) {
        return $messages;
    }
    
    foreach ($messages as &$message) {
        if (isset($message['MESSAGE_DATE'])) {
            if (is_object($message['MESSAGE_DATE'])) {
                $message['MESSAGE_DATE'] = $message['MESSAGE_DATE']->format('Y-m-d H:i:s');
            }
        }
    }
    
    return $messages;
}

// FIX: Add require_once BEFORE using the class
if (isset($_POST['acao']) && $_POST['acao'] === 'marcar_chat_lido') {
    header('Content-Type: application/json; charset=utf-8');
    
    // ADD THIS LINE
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

    $cod_usu = isset($_SESSION['cod_usu']) ? (int)$_SESSION['cod_usu'] : 0;
    if ($cod_usu <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sessão expirada ou usuário inválido.']);
        exit;
    }

    $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
    if ($chatId <= 0) {
        echo json_encode(['success' => false, 'message' => 'chat_id inválido.']);
        exit;
    }

    try {
        $model = new Analise();
        $ok = $model->markChatMessagesAsReadButton($chatId, $cod_usu);

        echo json_encode([
            'success' => (bool)$ok,
            'message' => $ok ? 'Mensagens marcadas como lidas.' : 'Nenhuma mensagem atualizada.',
        ]);
    } catch (Throwable $e) {
        error_log('markChatAsRead error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
    exit;
}

// ... rest of the file remains the same