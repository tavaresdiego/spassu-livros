<?php

namespace App\Report;

use Doctrine\DBAL\Connection;

/**
 * Relatório de livros agrupados por autor, lido direto da view vw_livros_por_autor via DBAL.
 * Um livro com N autores aparece em N grupos (a view tem uma linha por par livro-autor).
 */
final class BooksByAuthorReport
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return list<array{codAu: int, nome: string, livros: list<array<string, mixed>>, total: int, valorTotal: string}>
     */
    public function generate(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT CodAu, NomeAutor, Codl, Titulo, Editora, Edicao, AnoPublicacao, Valor, Assuntos
             FROM vw_livros_por_autor
             ORDER BY NomeAutor, CodAu, Titulo, Codl'
        );

        $groups = [];
        $cents = [];
        foreach ($rows as $row) {
            $codAu = (int) $row['CodAu'];
            $groups[$codAu] ??= ['codAu' => $codAu, 'nome' => $row['NomeAutor'], 'livros' => [], 'total' => 0, 'valorTotal' => '0.00'];
            $groups[$codAu]['livros'][] = $row;
            ++$groups[$codAu]['total'];
            $cents[$codAu] = ($cents[$codAu] ?? 0) + self::toCents((string) $row['Valor']);
        }

        foreach ($cents as $codAu => $total) {
            $groups[$codAu]['valorTotal'] = sprintf('%d.%02d', intdiv($total, 100), $total % 100);
        }

        return array_values($groups);
    }

    private static function toCents(string $decimal): int
    {
        [$int, $frac] = explode('.', $decimal.'.');

        return (int) $int * 100 + (int) str_pad(substr($frac, 0, 2), 2, '0');
    }
}
