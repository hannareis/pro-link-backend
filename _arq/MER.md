# Modelo Entidade-Relacionamento (MER) — CREA Pro-Link

> **Pendencia de entrega (Anexo I, item 8.3.2-b):** o edital exige o MER em
> **PDF, PNG e/ou arquivo editavel `.mwb` (MySQL Workbench)**. O diagrama Mermaid
> abaixo é apenas um auxiliar de leitura gerado a partir de `_arq/estrutura.sql` —
> antes da entrega final, importe `estrutura.sql` no MySQL Workbench (Database >
> Reverse Engineer) e exporte o `.mwb` e um PNG/PDF do diagrama.

```mermaid
erDiagram
    SIS_USUARIOS ||--o{ PRO_PORTFOLIOS : "possui (por_usu_id)"
    SIS_USUARIOS ||--o{ PRO_PORTFOLIOS : "valida como RT (por_responsavel_tecnico_usu_id)"
    SIS_USUARIOS ||--o{ PRO_DEMANDAS : "publica (dem_usu_id)"
    SIS_USUARIOS ||--o{ PRO_ARTS_CATS : "possui (art_usu_id)"
    SIS_USUARIOS ||--o{ SIS_AUDITORIA : "gera (aud_usu_id)"

    SIS_USUARIOS {
        int usu_id PK
        varchar usu_nome
        varchar usu_email
        varchar usu_senha_hash
        enum usu_perfil
        tinyint usu_selo_verificacao
        char usu_status
    }
    PRO_PORTFOLIOS {
        int por_id PK
        int por_usu_id FK
        int por_responsavel_tecnico_usu_id FK
        text por_resumo
        char por_status
    }
    PRO_DEMANDAS {
        int dem_id PK
        int dem_usu_id FK
        varchar dem_titulo
        text dem_escopo
        char dem_status
    }
    PRO_ARTS_CATS {
        int art_id PK
        int art_usu_id FK
        enum art_tipo
        varchar art_numero_rnp
        char art_status
    }
    SIS_AUDITORIA {
        int aud_id PK
        int aud_usu_id FK
        varchar aud_acao
        char aud_status
    }
```
