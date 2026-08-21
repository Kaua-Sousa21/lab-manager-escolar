# LabManager Escolar — instalação simples na Hostinger

Sistema em PHP + MySQL para professores consultarem e agendarem equipamentos da escola.

## Instalação: somente 4 passos

### 1. Crie o banco na Hostinger

No **hPanel**, abra **Bancos de dados → Gerenciamento MySQL** e crie o banco e o usuário.

Guarde estes dados:

- Host MySQL
- Nome do banco
- Usuário MySQL
- Senha MySQL

### 2. Importe o banco

Abra o **phpMyAdmin**, selecione o banco que você criou, clique em **Importar** e envie:

`database/IMPORTAR_NO_PHPMYADMIN.sql`

Não precisa editar o arquivo SQL.

### 3. Coloque os dados do banco em UM arquivo

No Gerenciador de Arquivos da Hostinger, abra:

`config/banco.php`

Você verá exatamente isto:

```php
return [
    'host' => 'localhost',
    'name' => 'COLE_AQUI_O_NOME_DO_BANCO',
    'user' => 'COLE_AQUI_O_USUARIO_DO_BANCO',
    'pass' => 'COLE_AQUI_A_SENHA_DO_BANCO',
];
```

Troque somente os textos entre aspas pelos dados da Hostinger e salve.

Exemplo fictício:

```php
return [
    'host' => 'localhost',
    'name' => 'u123456789_labmanager',
    'user' => 'u123456789_admin',
    'pass' => 'MinhaSenha123',
];
```

> Use os SEUS dados. Os valores acima são apenas exemplo.

### 4. Teste

Abra no navegador:

`SEU-DOMINIO/lab-manager-escolar/teste-banco.php`

Se aparecer **“Conexão com o banco realizada com sucesso!”**, clique em **Abrir o LabManager**.

No primeiro acesso, o sistema permitirá criar o primeiro administrador. Depois, crie uma conta para cada professor com o perfil **Professor**.

## Se der erro

Não mexa nos outros arquivos PHP. Confira apenas:

1. se o arquivo `config/banco.php` está com os dados completos;
2. se o usuário MySQL está vinculado ao banco na Hostinger;
3. se o arquivo `database/IMPORTAR_NO_PHPMYADMIN.sql` foi importado no banco correto;
4. se a hospedagem está usando PHP 8.1, 8.2 ou 8.3 com PDO MySQL habilitado.

## Fluxo do professor

Professor entra com sua conta → abre Equipamentos → clica em **Agendar** → escolhe data e horário → o sistema verifica conflito → reserva é salva no nome do professor.

A equipe administrativa continua responsável por confirmar retirada e devolução.
