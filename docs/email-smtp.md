Envio pela conta de e-mail
=========================

Responder e encaminhar usam a conta aberta na caixa de entrada: o endereço email_user é o remetente e o usuário SMTP; a senha criptografada pass_user é descriptografada somente no servidor para autenticar o envio. A configuração global ufrgsMail não é mais utilizada.

Em `/email/edit/{id}`, configure o servidor, a porta e a segurança SMTP da conta. O envio exige um Servidor SMTP explícito e nunca usa o servidor IMAP/POP3 como alternativa. Sem SMTP, a conta continua disponível para receber mensagens. A porta padrão de envio é 587 com STARTTLS. Para TLS implícito, selecione SSL e a porta fornecida pelo provedor (normalmente 465).

Responder envia ao remetente original. Encaminhar envia exclusivamente ao endereço do perfil autenticado. O conteúdo original é incluído como texto, sem anexos. Nenhuma mensagem é enviada automaticamente ao abrir a caixa de entrada.
