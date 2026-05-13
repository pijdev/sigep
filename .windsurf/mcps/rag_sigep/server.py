#!/usr/bin/env python3
"""
MCP Server RAG SIGEP - Servidor especializado em RAG para o Sistema Prisional Integrado SIGEP
"""

import asyncio
import json
import logging
import os
import sys
from pathlib import Path
from typing import Any, Dict, List

# Configuração logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Importações MCP
from mcp.server import Server
from mcp.server.stdio import stdio_server
from mcp.server.models import InitializationOptions, ServerCapabilities
from mcp.types import CallToolResult, TextContent, Tool

# Configurações
SIGEP_PATH = os.getenv("SIGEP_PATH", "C:\\Sites\\sigep")
OLLAMA_HOST = os.getenv("OLLAMA_HOST", "http://localhost:11434")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3.2")

# Base de conhecimento SIGEP
SIGEP_KNOWLEDGE = {
    "modules": {
        "internos": "Módulo principal de gestão de internos do sistema prisional. Controla cadastro, dados pessoais, fotos, histórico criminal.",
        "movimentacoes": "Controla transferências e movimentações de presos entre unidades prisionais. Inclui transferências, saídas temporárias, escoltas.",
        "censura": "Sistema de censura de correspondências dos internos. Analisa cartas, emails, comunicações por segurança.",
        "laboral": "Gestão de atividades laborais dos internos. Controla trabalhos internos, externalização, remuneração.",
        "saude": "Controle de saúde e atendimentos médicos. Prontuários, medicamentos, consultas, emergências.",
        "educacao": "Sistema educacional para internos. Aulas, cursos, certificados, acompanhamento pedagógico.",
        "visitas": "Controle de visitas de familiares. Agendamento, autorização, registro de ocorrências.",
        "disciplinar": "Sistema disciplinar. Faltas, sanções, relatórios, processo administrativo.",
        "patrimonio": "Gestão de patrimônio da unidade. Bens, materiais, manutenção, inventário.",
        "alimentacao": "Controle de alimentação. Cardápios, dietas especiais, estoque, nutrição."
    },
    "database": {
        "internos": "Tabela principal com dados dos presos: id, nome, cpf, rg, data_nascimento, status, unidade_id",
        "movimentacoes": "Registro de transferências: id, interno_id, unidade_origem, unidade_destino, data, motivo",
        "censura_cartas": "Controle de cartas censuradas: id, interno_id, remetente, destinatario, status, data",
        "unidades": "Dados das unidades prisionais: id, nome, endereco, capacidade, diretor",
        "usuarios": "Usuários do sistema: id, nome, login, senha_hash, perfil, permissões",
        "auditoria": "Log de auditoria: id, usuario_id, acao, tabela, registro_id, data"
    },
    "patterns": {
        "auth": "session_start(), validação de usuário em auth/, controle de permissões com $_SESSION['user_admin']",
        "crud": "PDO prepared statements, validação de inputs, UTF-8, transações com begin/commit/rollback",
        "mvc": "View em paginas/, Controller em includes/, Models em models/ ou diretamente no controller",
        "api": "Endpoints AJAX em *_logica.php, retorno JSON com header('Content-Type: application/json')",
        "security": "filter_input(), htmlspecialchars(), prepared statements, validação de CSRF token",
        "adminlte": "Classes AdminLTE 3: card, small-box, btn, form-control, dataTable, Select2",
        "spa": "Navegação com loadPage(), history.pushState(), sem refresh completo da página"
    }
}


class SIGEPRAG:
    """Sistema RAG especializado no SIGEP"""

    def __init__(self):
        self.ollama_available = self._check_ollama()
        self.sigep_available = os.path.exists(SIGEP_PATH)

    def _check_ollama(self) -> bool:
        """Verifica se Ollama está disponível"""
        try:
            import subprocess
            result = subprocess.run(['ollama', 'list'], capture_output=True, text=True, timeout=5)
            return result.returncode == 0
        except:
            return False

    def query(self, question: str, context: str = "") -> str:
        """Executa query RAG especializada no SIGEP"""
        question_lower = question.lower()
        relevant_info = []

        # Buscar em módulos
        for module, desc in SIGEP_KNOWLEDGE["modules"].items():
            if module in question_lower or any(word in desc.lower() for word in question_lower.split()):
                relevant_info.append(f"📁 **Módulo {module.upper()}**: {desc}")

        # Buscar em banco de dados
        for table, desc in SIGEP_KNOWLEDGE["database"].items():
            if table in question_lower or any(word in desc.lower() for word in question_lower.split()):
                relevant_info.append(f"🗄️ **Tabela {table.upper()}**: {desc}")

        # Buscar em padrões
        for pattern, desc in SIGEP_KNOWLEDGE["patterns"].items():
            if pattern in question_lower or any(word in desc.lower() for word in question_lower.split()):
                relevant_info.append(f"⚙️ **Padrão {pattern.upper()}**: {desc}")

        # Gerar resposta com Ollama se disponível
        if self.ollama_available:
            try:
                import subprocess
                knowledge_text = "\n".join(relevant_info) if relevant_info else "Nenhuma informação específica encontrada"

                prompt = f"""Você é um especialista no Sistema Prisional Integrado SIGEP.
Responda à pergunta abaixo usando as informações disponíveis sobre o sistema.

Pergunta: {question}
Contexto adicional: {context}

Conhecimento SIGEP relevante:
{knowledge_text}

Responda de forma clara, objetiva e técnica sobre o SIGEP. Se não encontrar informação específica, indique isso claramente."""

                result = subprocess.run([
                    'ollama', 'run', OLLAMA_MODEL, prompt
                ], capture_output=True, text=True, timeout=30, encoding='utf-8')

                if result.returncode == 0:
                    ollama_response = result.stdout.strip()

                    if relevant_info:
                        return f"""## 🤖 **Resposta (via {OLLAMA_MODEL})**

{ollama_response}

---

## 📚 **Contexto SIGEP**
{chr(10).join(relevant_info)}"""
                    else:
                        return f"""## 🤖 **Resposta (via {OLLAMA_MODEL})**

{ollama_response}

---
*Baseado no conhecimento geral sobre o SIGEP*"""

            except Exception as e:
                logger.error(f"Erro ao consultar Ollama: {e}")

        # Resposta baseada apenas no conhecimento local
        if relevant_info:
            return f"""## 📋 **Resposta Baseada no Conhecimento SIGEP**

{chr(10).join(relevant_info)}

---
*Resposta gerada com base no conhecimento estruturado do SIGEP*"""
        else:
            return f"""## ❓ **Informação Não Encontrada**

Não encontrei informações específicas sobre '{question}' no conhecimento do SIGEP.

**Módulos disponíveis**: {', '.join(SIGEP_KNOWLEDGE['modules'].keys())}
**Tabelas principais**: {', '.join(SIGEP_KNOWLEDGE['database'].keys())}
**Padrões**: {', '.join(SIGEP_KNOWLEDGE['patterns'].keys())}

Tente perguntar sobre um desses tópicos."""


# Instanciar sistema RAG
rag_system = SIGEPRAG()

# Inicialização do servidor MCP
server = Server("rag-sigep")


@server.list_tools()
async def handle_list_tools() -> List[Tool]:
    """Lista ferramentas disponíveis"""
    return [
        Tool(
            name="rag_query",
            description="Executa consulta RAG especializada no conhecimento do SIGEP",
            inputSchema={
                "type": "object",
                "properties": {
                    "query": {
                        "type": "string",
                        "description": "A consulta ou pergunta sobre o SIGEP"
                    },
                    "context": {
                        "type": "string",
                        "description": "Contexto adicional para refinar a busca (opcional)"
                    }
                },
                "required": ["query"]
            }
        ),
        Tool(
            name="list_sigep_modules",
            description="Lista todos os módulos do sistema SIGEP com descrições",
            inputSchema={
                "type": "object",
                "properties": {}
            }
        ),
        Tool(
            name="get_sigep_database",
            description="Obt informações sobre as tabelas do banco de dados SIGEP",
            inputSchema={
                "type": "object",
                "properties": {
                    "table": {
                        "type": "string",
                        "description": "Tabela específica (opcional, retorna todas se não especificado)"
                    }
                }
            }
        ),
        Tool(
            name="get_sigep_patterns",
            description="Obt padrões de código e desenvolvimento do SIGEP",
            inputSchema={
                "type": "object",
                "properties": {
                    "pattern": {
                        "type": "string",
                        "description": "Padrão específico (opcional, retorna todos se não especificado)"
                    }
                }
            }
        ),
        Tool(
            name="get_sigep_workflows",
            description="Obt fluxos de trabalho e processos do SIGEP",
            inputSchema={
                "type": "object",
                "properties": {
                    "workflow": {
                        "type": "string",
                        "description": "Workflow específico (opcional, retorna todos se não especificado)"
                    }
                }
            }
        )
    ]


@server.call_tool()
async def handle_call_tool(name: str, arguments: Dict[str, Any]) -> CallToolResult:
    """Executa chamadas de ferramentas"""

    try:
        if name == "rag_query":
            query = arguments.get("query", "")
            context = arguments.get("context", "")

            if not query:
                return CallToolResult(
                    content=[TextContent(
                        type="text",
                        text="❌ Erro: A query é obrigatória"
                    )],
                    isError=True
                )

            answer = rag_system.query(query, context)

            return CallToolResult(
                content=[TextContent(
                    type="text",
                    text=f"""# 🔍 **Consulta RAG - SIGEP**

## ❓ **Pergunta**
{query}

{answer}

## 📊 **Status do Sistema**
- 🤖 **Ollama**: {'✅ Disponível' if rag_system.ollama_available else '❌ Indisponível (usando modo simulado)'}
- 📁 **SIGEP Path**: {'✅ Encontrado' if rag_system.sigep_available else '❌ Não encontrado'}
- 🎯 **Modelo**: {OLLAMA_MODEL}
"""
                )]
            )

        elif name == "list_sigep_modules":
            modules_text = "## 📚 **Módulos do Sistema SIGEP**\n\n"
            for module, desc in SIGEP_KNOWLEDGE["modules"].items():
                modules_text += f"### **{module.upper()}**\n{desc}\n\n"

            modules_text += f"**Total**: {len(SIGEP_KNOWLEDGE['modules'])} módulos principais\n"

            return CallToolResult(
                content=[TextContent(type="text", text=modules_text)]
            )

        elif name == "get_sigep_database":
            table = arguments.get("table", "")

            if table:
                if table in SIGEP_KNOWLEDGE["database"]:
                    db_text = f"## 🗄️ **Tabela: {table.upper()}**\n\n{SIGEP_KNOWLEDGE['database'][table]}"
                else:
                    db_text = f"## ❌ **Tabela não encontrada**: {table}\n\n**Tabelas disponíveis**: {', '.join(SIGEP_KNOWLEDGE['database'].keys())}"
            else:
                db_text = "## 🗄️ **Banco de Dados SIGEP**\n\n"
                for table, desc in SIGEP_KNOWLEDGE["database"].items():
                    db_text += f"### **{table.upper()}**\n{desc}\n\n"
                db_text += f"**Total**: {len(SIGEP_KNOWLEDGE['database'])} tabelas principais\n"

            return CallToolResult(
                content=[TextContent(type="text", text=db_text)]
            )

        elif name == "get_sigep_patterns":
            pattern = arguments.get("pattern", "")

            if pattern:
                if pattern in SIGEP_KNOWLEDGE["patterns"]:
                    pattern_text = f"## ⚙️ **Padrão: {pattern.upper()}**\n\n{SIGEP_KNOWLEDGE['patterns'][pattern]}"
                else:
                    pattern_text = f"## ❌ **Padrão não encontrado**: {pattern}\n\n**Padrões disponíveis**: {', '.join(SIGEP_KNOWLEDGE['patterns'].keys())}"
            else:
                pattern_text = "## ⚙️ **Padrões de Desenvolvimento SIGEP**\n\n"
                for pattern, desc in SIGEP_KNOWLEDGE["patterns"].items():
                    pattern_text += f"### **{pattern.upper()}**\n{desc}\n\n"
                pattern_text += f"**Total**: {len(SIGEP_KNOWLEDGE['patterns'])} padrões\n"

            return CallToolResult(
                content=[TextContent(type="text", text=pattern_text)]
            )

        elif name == "get_sigep_workflows":
            workflow = arguments.get("workflow", "")

            # Adicionar workflows ao conhecimento se não existir
            if "workflows" not in SIGEP_KNOWLEDGE:
                SIGEP_KNOWLEDGE["workflows"] = {
                    "login": "auth/login.php → auth/login_logica.php → validação → session_start() → dashboard",
                    "cadastro_interno": "paginas/internos_cadastro.php → includes/internos_logica.php → INSERT → auditoria",
                    "movimentacao": "paginas/movimentacoes.php → includes/movimentacoes_logica.php → validação → transferência",
                    "censura": "paginas/censura.php → includes/censura_logica.php → análise → aprovação/reprovação"
                }

            if workflow:
                if workflow in SIGEP_KNOWLEDGE["workflows"]:
                    workflow_text = f"## 🔄 **Workflow: {workflow.upper()}**\n\n{SIGEP_KNOWLEDGE['workflows'][workflow]}"
                else:
                    workflow_text = f"## ❌ **Workflow não encontrado**: {workflow}\n\n**Workflows disponíveis**: {', '.join(SIGEP_KNOWLEDGE['workflows'].keys())}"
            else:
                workflow_text = "## 🔄 **Workflows e Processos SIGEP**\n\n"
                for workflow, desc in SIGEP_KNOWLEDGE["workflows"].items():
                    workflow_text += f"### **{workflow.upper()}**\n{desc}\n\n"
                workflow_text += f"**Total**: {len(SIGEP_KNOWLEDGE['workflows'])} workflows\n"

            return CallToolResult(
                content=[TextContent(type="text", text=workflow_text)]
            )

        else:
            return CallToolResult(
                content=[TextContent(
                    type="text",
                    text=f"❌ Ferramenta desconhecida: {name}"
                )],
                isError=True
            )

    except Exception as e:
        logger.error(f"Erro na ferramenta {name}: {e}")
        return CallToolResult(
            content=[TextContent(
                type="text",
                text=f"❌ Erro ao executar {name}: {str(e)}"
            )],
            isError=True
        )


async def main():
    """Função principal do servidor MCP"""
    logger.info("🚀 Iniciando MCP Server RAG SIGEP...")
    logger.info(f"📁 SIGEP Path: {SIGEP_PATH}")
    logger.info(f"🤖 Ollama: {OLLAMA_HOST} - {OLLAMA_MODEL}")
    logger.info(f"✅ Ollama Disponível: {rag_system.ollama_available}")
    logger.info(f"✅ SIGEP Disponível: {rag_system.sigep_available}")

    async with stdio_server() as (read_stream, write_stream):
        capabilities = ServerCapabilities(tools={})
        init_options = InitializationOptions(
            server_name="rag-sigep",
            server_version="1.0.0",
            capabilities=capabilities
        )
        await server.run(read_stream, write_stream, init_options)


if __name__ == "__main__":
    asyncio.run(main())
