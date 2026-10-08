<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * View do relatório: uma linha por par livro-autor, com os assuntos do livro concatenados.
 * Ignorada pelo schema_filter do DBAL (vw_*), para que migrations:diff não tente removê-la.
 */
final class Version20261006190216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria a view vw_livros_por_autor (relatório de livros agrupados por autor)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE VIEW vw_livros_por_autor AS
            SELECT
                a.CodAu         AS CodAu,
                a.Nome          AS NomeAutor,
                l.Codl          AS Codl,
                l.Titulo        AS Titulo,
                l.Editora       AS Editora,
                l.Edicao        AS Edicao,
                l.AnoPublicacao AS AnoPublicacao,
                l.Valor         AS Valor,
                (
                    SELECT GROUP_CONCAT(s.Descricao ORDER BY s.Descricao SEPARATOR ', ')
                    FROM Livro_Assunto ls
                    INNER JOIN Assunto s ON s.codAs = ls.Assunto_codAs
                    WHERE ls.Livro_Codl = l.Codl
                ) AS Assuntos
            FROM Autor a
            INNER JOIN Livro_Autor la ON la.Autor_CodAu = a.CodAu
            INNER JOIN Livro l ON l.Codl = la.Livro_Codl
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS vw_livros_por_autor');
    }
}
