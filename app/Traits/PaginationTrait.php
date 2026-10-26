<?php

namespace App\Traits;

trait PaginationTrait
{

    public $paginationData = [
        'default' => [
            'currentPage' => 1,
            'perPage' => 10,
            'search' => '',
            'totalPage' => 0,
        ],
    ];


    /**
     * Mengubah halaman berdasarkan arah (next, previous, first, last).
     * @param string $direction
     * @param string $key
     * @return void
     */
    public function changePage($direction, $key)
    {

        $lastPage = ceil($this->paginationData[$key]['totalPage'] / $this->paginationData[$key]['perPage']);

        if ($direction === 'next' && $this->paginationData[$key]['currentPage'] < $lastPage) {
            $this->paginationData[$key]['currentPage']++;
        } elseif ($direction === 'previous' && $this->paginationData[$key]['currentPage'] > 1) {
            $this->paginationData[$key]['currentPage']--;
        } elseif ($direction === 'first') {
            $this->paginationData[$key]['currentPage'] = 1;
        } elseif ($direction === 'last') {
            $this->paginationData[$key]['currentPage'] = $lastPage;
        }
    }

    /**
     * Memperbarui halaman ke nomor tertentu.
     * @param int $page
     * @param string $key
     * @return void
     */
    public function updatePage($page, $key)
    {
        $lastPage = ceil($this->paginationData[$key]['totalPage'] / $this->paginationData[$key]['perPage']);
        $this->paginationData[$key]['currentPage'] = max(1, min($page, $lastPage));
    }

    /**
     * Menyetel ulang halaman ke halaman pertama.
     * @return void
     */
    public function reloadPage($key)
    {
        $this->paginationData[$key]['currentPage'] = 1;
    }

    /**
     * Memperbarui jumlah item yang ditampilkan per halaman.
     * @param int $perPage
     * @param string $key
     * @return void
     */
    public function updatePerPage($perPageLov, $key)
    {
        $this->paginationData[$key]['perPage'] = $perPageLov;
        $this->paginationData[$key]['currentPage'] = 1; // Reset halaman ke 1
    }


    /**
     * Memperbarui query pencarian dan reset halaman ke 1.
     */
    public function updatedSearch($key)
    {
        $this->paginationData[$key]['currentPage'] = 1;
    }


    public function updatedPaginationData($value, $key)
    {
        if (str_ends_with($key, 'search')) {
            $segments = explode('.', $key);
            $dataKey = $segments[0];
            $this->reloadPage($dataKey); // Pastikan metode reloadPage digunakan
        }
    }


    /**
     * Menyetel total data untuk key tertentu.
     * @param int $total
     * @param string $key
     */
    public function setTotal($total, $key)
    {
        $this->paginationData[$key]['totalPage'] = $total;
    }

    /**
     * Mendapatkan data pagination untuk key tertentu.
     * @param string $key
     * @return array
     */
    public function getPaginationData($key)
    {
        return $this->paginationData[$key];
    }
}
