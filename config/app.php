<?php
namespace Config;

class App
{
    ## GERAL
    public const APP_NAME = 'Sistema Prisional Integrado';
    public const APP_NAME_SHORT = 'SIGEP';
    public const APP_VERSION = '1.1.0';
    public const APP_CREATED_AT = '2025';
    public const APP_DEV = 'PIJ';
    public const APP_DEV_EMAIL = 'dev01@sigep.pij.local';
    public const APP_LICENSE = 'MIT';
    public const APP_URL = 'http://sigep-hml.pij.local/';
    public const APP_DEBUG = true;
    public const APP_ENV = 'dev';
    public const APP_ENCODING = 'utf-8';
    public const APP_TIMEZONE = 'America/Sao_Paulo';
    public const APP_LOCALE = 'pt_BR';
    public const APP_LOCALE_DEFAULT = 'pt_BR';
    public const APP_LOCALE_FALLBACK = 'pt_BR';

    ## SESSAO
    public const SESSION_DRIVER = 'redis';
    public const SESSION_LIFETIME=120;
    public const SESSION_ENCRYPT=false;
    public const SESSION_PATH=null;
    public const SESSION_DOMAIN=null;

    ## BROADCASTING
    public const BROADCAST_CONNECTION = 'log';
    public const FILESYSTEM_DISK = 'local';
    public const QUEUE_CONNECTION = 'redis';

    ## REDIS
    public const REDIS_CLIENT = 'phpredis';
    public const REDIS_HOST = '127.0.0.1';
    public const REDIS_PASSWORD = 'FUXiejhuIHWK8VpRE02qyszJdTP6OLv4';
    public const REDIS_PORT = 6379;
    public const REDIS_DB = 0;
    
    ## E-MAIL
    public const MAIL_MAILER = 'log';
    public const MAIL_SCHEME = null;
    public const MAIL_HOST = '127.0.0.1';
    public const MAIL_PORT = 2525;
    public const MAIL_USERNAME = null;
    public const MAIL_PASSWORD = null;
    public const MAIL_FROM_ADDRESS = 'hello@example.com';
    public const MAIL_FROM_NAME = self::APP_NAME_SHORT;

    ## VITE
    public const VITE_APP_NAME = self::APP_NAME_SHORT;
}
