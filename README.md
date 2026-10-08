# Teste técnico Spassu
# ![SpassuLivros](/public/images/logo-spassu-livros.svg)
Cadastro de **livros, autores e assuntos** com catálogo público, links (CRUD) e **relatório de livros agrupados por autor** (tela e PDF), feito com Symfony 7.4 LTS e Twig.

- Backend: PHP 8.3, Symfony 7.4 LTS, Doctrine ORM + Migrations, MySQL 8.0 (utf8mb4).
- Frontend: Twig com componentes anônimos (`symfony/ux-twig-component`), Bootstrap 5.3.8 via CDN, CSS próprio em `public/css/custom.css` e JS vanilla em `public/js/app.js`. Sem Node nem bundlers.
- PDF: Dompdf.

## Requisitos

- Docker com Docker Compose v2.
- `make`.
- Portas livres: **8080** (HTTP) e **3307** (MySQL no host). Para trocar: `HTTP_PORT=8081 MYSQL_PORT=3308 make build`.

## Subindo o projeto

```bash
make build
```

Esse comando irá subir os containers e realizar as configurações iniciais da aplicação, instalação das dependências, banco de dados e ainda carrega os dados mockados (a partir de `data/books.json`).

Depois disso, acesse **http://localhost:8080**.

## Comandos do Makefile

| Comando | O que faz |
|---|---|
| `make build` | Sobe tudo do zero (imagens, dependências, banco, migrations, dados mockados de livros, banco de teste) |
| `make test` | Roda a suíte do PHPUnit no banco `_test` |
| `make reset` | Apaga e recria o banco de desenvolvimento, migrations e dados mockados dos livros, e prepara o banco de teste |

## Dados de exemplo (data/books.json)

São carregados pelo `data/books.json` (versionado), então `make build`/`make reset` funcionam sem internet e sempre recriam a mesma base inicial: 6 assuntos, até 60 livros (alguns em dois assuntos) e seus autores. Para alterar os dados iniciais, edite o JSON e rode `make reset`.

```bash
make reset
```


### Styleguide (documentação viva)

Com `APP_ENV=dev` (padrão do `make build`), abra **http://localhost:8080/styleguide**. A página mostra todos os componentes com variantes e estados e um exemplo de uso de cada um. A rota é declarada com `env: ['dev', 'test']` e não existe em produção.

### Catálogo de componentes

Todos ficam em `templates/components/<Pasta>/<Nome>.html.twig` e são usados como `<twig:Pasta:Nome />`. Todos aceitam atributos HTML extras (`class`, `id`, `data-*`, `aria-*`), repassados por `{{ attributes }}`.
