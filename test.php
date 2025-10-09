// Replace the chat methods in analise_encerramento_model.class.php

public function createChatIfNotExists($cod_solicitacao) {
    $query = "SELECT CHAT_ID FROM MESU..ENCERRAMENTO_TB_PORTAL WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    $result = $this->sql->select($query);
    
    if (!$result || !isset($result[0]['CHAT_ID']) || !$result[0]['CHAT_ID']) {
        // Use COD_SOLICITACAO as CHAT_ID (each solicitacao has one chat)
        $chatId = intval($cod_solicitacao);
        
        // Update main table with chat_id
        $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL SET CHAT_ID = " . $chatId . 
                      " WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
        $this->sql->update($updateQuery);
        
        return $chatId;
    }
    
    return intval($result[0]['CHAT_ID']);
}

public function getChatMessagesByGroup($chat_id, $user_group, $target_group) {
    // Messages are visible if:
    // 1. User is sender and target is recipient_group OR
    // 2. User's group is recipient_group and sender_group is target
    
    $query = "SELECT m.*, 
              f.nome_func as REMETENTE_NOME,
              m.SENDER_GROUP,
              m.RECIPIENT_GROUP
              FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT m
              LEFT JOIN RH..TB_FUNCIONARIOS f ON m.REMETENTE = f.COD_FUNC
              WHERE m.CHAT_ID = " . intval($chat_id) . "
              AND (
                  (m.SENDER_GROUP = '" . addslashes($user_group) . "' AND m.RECIPIENT_GROUP = '" . addslashes($target_group) . "')
                  OR
                  (m.SENDER_GROUP = '" . addslashes($target_group) . "' AND m.RECIPIENT_GROUP = '" . addslashes($user_group) . "')
              )
              ORDER BY m.MESSAGE_DATE ASC";
    
    $result = $this->sql->select($query);
    
    // Ensure we always return an array
    if (!$result) {
        return [];
    }
    
    return $result;
}

public function sendChatMessageToGroup($chat_id, $mensagem, $remetente, $sender_group, $recipient_group, $anexo = 0) {
    $query = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
              (CHAT_ID, MENSAGEM, DESTINATARIO, REMETENTE, SENDER_GROUP, RECIPIENT_GROUP, ANEXO) 
              VALUES (" . intval($chat_id) . ", 
                     '" . addslashes($mensagem) . "', 
                     0, 
                     " . intval($remetente) . ", 
                     '" . addslashes($sender_group) . "',
                     '" . addslashes($recipient_group) . "',
                     " . intval($anexo) . ")";
    return $this->sql->insert($query);
}

public function getLastMessageId() {
    $query = "SELECT IDENT_CURRENT('MESU.ENCERRAMENTO_TB_PORTAL_CHAT') as MESSAGE_ID";
    $result = $this->sql->select($query);
    return intval($result[0]['MESSAGE_ID']);
}

// Keep old method for backward compatibility if needed
public function getChatMessages($chat_id) {
    return $this->getChatMessagesByGroup($chat_id, '', '');
}


---------


-- Database Update Script for Group-Based Chat System
-- Run this script to add group tracking columns to the chat table

-- 1. Add SENDER_GROUP column
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'MESU' 
      AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT' 
      AND COLUMN_NAME = 'SENDER_GROUP'
)
BEGIN
    ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
    ADD SENDER_GROUP VARCHAR(50) NULL;
    PRINT 'SENDER_GROUP column added successfully';
END
ELSE
BEGIN
    PRINT 'SENDER_GROUP column already exists';
END;

-- 2. Add RECIPIENT_GROUP column
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'MESU' 
      AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT' 
      AND COLUMN_NAME = 'RECIPIENT_GROUP'
)
BEGIN
    ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
    ADD RECIPIENT_GROUP VARCHAR(50) NULL;
    PRINT 'RECIPIENT_GROUP column added successfully';
END
ELSE
BEGIN
    PRINT 'RECIPIENT_GROUP column already exists';
END;

-- 3. Initialize CHAT_ID for existing records (use COD_SOLICITACAO as CHAT_ID)
UPDATE MESU..ENCERRAMENTO_TB_PORTAL 
SET CHAT_ID = COD_SOLICITACAO 
WHERE CHAT_ID IS NULL;
PRINT 'CHAT_ID initialized for existing records';

-- 4. Create index for better query performance
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes 
    WHERE name = 'IDX_CHAT_GROUPS' 
      AND object_id = OBJECT_ID('MESU..ENCERRAMENTO_TB_PORTAL_CHAT')
)
BEGIN
    CREATE INDEX IDX_CHAT_GROUPS 
    ON MESU..ENCERRAMENTO_TB_PORTAL_CHAT(CHAT_ID, SENDER_GROUP, RECIPIENT_GROUP);
    PRINT 'Index on chat groups created';
END
ELSE
BEGIN
    PRINT 'Index on chat groups already exists';
END;

-- 5. Verify the updated structure
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE,
    CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'MESU' 
  AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT'
ORDER BY ORDINAL_POSITION;

-- Expected columns:
-- MESSAGE_ID (int, NOT NULL, IDENTITY)
-- CHAT_ID (int, NOT NULL)
-- MENSAGEM (varchar(4000), NULL)
-- DESTINATARIO (int, NOT NULL)
-- REMETENTE (int, NOT NULL)
-- ANEXO (bit, NOT NULL)
-- MESSAGE_DATE (datetime, NOT NULL)
-- SENDER_GROUP (varchar(50), NULL) - NEW
-- RECIPIENT_GROUP (varchar(50), NULL) - NEW

-- 6. Update existing messages with default groups (optional)
-- Only run this if you have existing messages that need group assignment
/*
UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT
SET SENDER_GROUP = 'ENC_MANAGEMENT',
    RECIPIENT_GROUP = 'OP_MANAGEMENT'
WHERE SENDER_GROUP IS NULL;
PRINT 'Existing messages assigned default groups';
*/

-- 7. Test the new structure
-- This will show if any messages exist and their group information
SELECT TOP 10
    MESSAGE_ID,
    CHAT_ID,
    REMETENTE,
    DESTINATARIO,
    SENDER_GROUP,
    RECIPIENT_GROUP,
    SUBSTRING(MENSAGEM, 1, 50) as MENSAGEM_PREVIEW,
    MESSAGE_DATE
FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT
ORDER BY MESSAGE_DATE DESC;

-- 8. Verify CHAT_ID setup in main table
SELECT TOP 10
    COD_SOLICITACAO,
    CHAT_ID,
    CHAVE_LOJA,
    STATUS_SOLIC
FROM MESU..ENCERRAMENTO_TB_PORTAL
ORDER BY COD_SOLICITACAO DESC;
-- CHAT_ID should equal COD_SOLICITACAO for all records

-- 9. Performance check - Ensure indexes exist
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes 
    WHERE name = 'IDX_CHAT_ID' 
      AND object_id = OBJECT_ID('MESU..ENCERRAMENTO_TB_PORTAL_CHAT')
)
BEGIN
    CREATE INDEX IDX_CHAT_ID ON MESU..ENCERRAMENTO_TB_PORTAL_CHAT(CHAT_ID);
    PRINT 'Index on CHAT_ID created';
END;

-- 10. Final verification summary
SELECT 
    'ENCERRAMENTO_TB_PORTAL' as TABLE_NAME,
    COUNT(*) as TOTAL_RECORDS,
    SUM(CASE WHEN CHAT_ID IS NOT NULL THEN 1 ELSE 0 END) as WITH_CHAT,
    SUM(CASE WHEN CHAT_ID IS NULL THEN 1 ELSE 0 END) as WITHOUT_CHAT,
    SUM(CASE WHEN CHAT_ID = COD_SOLICITACAO THEN 1 ELSE 0 END) as CORRECT_CHAT_ID
FROM MESU..ENCERRAMENTO_TB_PORTAL
UNION ALL
SELECT 
    'ENCERRAMENTO_TB_PORTAL_CHAT' as TABLE_NAME,
    COUNT(*) as TOTAL_RECORDS,
    COUNT(DISTINCT CHAT_ID) as UNIQUE_CHATS,
    SUM(CASE WHEN SENDER_GROUP IS NOT NULL THEN 1 ELSE 0 END) as WITH_SENDER_GROUP,
    SUM(CASE WHEN RECIPIENT_GROUP IS NOT NULL THEN 1 ELSE 0 END) as WITH_RECIPIENT_GROUP
FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT;

PRINT '';
PRINT 'Database update complete!';
PRINT 'CHAT_ID is set to COD_SOLICITACAO (each request has one chat)';
PRINT 'Groups: OP_MANAGEMENT, COM_MANAGEMENT, BLOQ_MANAGEMENT, ENC_MANAGEMENT';
PRINT '';
PRINT 'Note: DESTINATARIO column is kept for backward compatibility but set to 0';
PRINT 'Group routing now uses SENDER_GROUP and RECIPIENT_GROUP columns'; 