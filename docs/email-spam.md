# Remetentes de SPAM

Antes de publicar esta versão, execute `php spark migrate` no servidor para criar `email_spam_senders`.

Na leitura de uma mensagem, o botão de escudo ao lado da lixeira adiciona o endereço do remetente à lista pessoal de SPAM. A lista aparece em “Remetentes de SPAM” na caixa de entrada e vale para todas as contas do usuário. A comparação usa o endereço completo, sem diferenciar maiúsculas e minúsculas, ignorando o nome de exibição.

O cadastro classifica as mensagens já armazenadas desse remetente como `email_status = 4`; novas mensagens recebem essa classificação durante a importação. Mensagens SPAM aparecem apenas na aba SPAM, sem entrar em Todas, Não lidas, Importantes ou Urgentes. A leitura automática não altera essa classificação. Nenhuma mensagem é apagada ou movida no servidor de e-mail por essa ação.
