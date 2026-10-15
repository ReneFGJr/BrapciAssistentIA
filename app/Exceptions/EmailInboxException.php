<?php
namespace App\Exceptions;

use RuntimeException;

// Messages in this exception are safe to display; never include raw IMAP errors.
class EmailInboxException extends RuntimeException
{
    public static function connectionFailure(array $errors): self
    {
        $detail = strtolower(implode(' ', $errors));
        if (preg_match('/certificate|certificat|self.signed|unable to get.*issuer/', $detail)) return new self('O PHP de produção não confia no certificado do servidor de e-mail. Instale a cadeia de certificados confiável no servidor de produção.');
        if (preg_match('/authentication|authenticate|login failed|invalid credentials|password|authorization/', $detail)) return new self('O servidor recusou a autenticação. Confira a senha ou senha de aplicativo cadastrada nesta conta.');
        if (preg_match('/timed out|timeout/', $detail)) return new self('Tempo limite na conexão com o servidor de e-mail. Verifique a liberação de saída para a porta configurada no servidor de produção.');
        if (preg_match('/resolve|host not found|name or service not known|nodename/', $detail)) return new self('O servidor de produção não conseguiu resolver o endereço do servidor de e-mail. Confira o hostname e o DNS.');
        if (preg_match('/refused|unreachable|network|connect|connection|socket/', $detail)) return new self('O servidor de produção não conseguiu abrir a conexão de e-mail. Verifique hostname, porta, segurança e firewall de saída.');
        return new self('Não foi possível abrir a caixa de entrada. Confira servidor, porta, segurança e autenticação desta conta no ambiente de produção.');
    }
}
