<?php

namespace App\Traits;

trait PaginationLov
{
    // Menyimpan informasi pagination untuk berbagai key
    public $paginationDataLov = [
        'default' => [
            'currentPageLov' => 1,
            'perPageLov' => 10,
            'searchLov' => '',
            'totalLov' => 0,
        ],
    ];



    /**
     * Mengubah halaman berdasarkan arah (next, previous, first, last).
     * @param string $direction
     * @param string $key
     * @return void
     */
    public function changePageLov($direction, $key)
    {

        $lastPage = ceil($this->paginationDataLov[$key]['totalLov'] / $this->paginationDataLov[$key]['perPageLov']);

        if ($direction === 'next' && $this->paginationDataLov[$key]['currentPageLov'] < $lastPage) {
            $this->paginationDataLov[$key]['currentPageLov']++;
        } elseif ($direction === 'previous' && $this->paginationDataLov[$key]['currentPageLov'] > 1) {
            $this->paginationDataLov[$key]['currentPageLov']--;
        } elseif ($direction === 'first') {
            $this->paginationDataLov[$key]['currentPageLov'] = 1;
        } elseif ($direction === 'last') {
            $this->paginationDataLov[$key]['currentPageLov'] = $lastPage;
        }
    }

    /**
     * Memperbarui halaman ke nomor tertentu.
     * @param int $page
     * @param string $key
     * @return void
     */
    public function updatePageLov($page, $key)
    {
        $lastPage = ceil($this->paginationDataLov[$key]['totalLov'] / $this->paginationDataLov[$key]['perPageLov']);
        $this->paginationDataLov[$key]['currentPageLov'] = max(1, min($page, $lastPage));
    }

    /**
     * Menyetel ulang halaman ke halaman pertama.
     * @return void
     */
    public function reloadPageLov($key)
    {
        $this->paginationDataLov[$key]['currentPageLov'] = 1;
    }

    /**
     * Memperbarui jumlah item yang ditampilkan per halaman.
     * @param int $perPageLov
     * @param string $key
     * @return void
     */
    public function updatePerPageLov($perPageLov, $key)
    {
        $this->paginationDataLov[$key]['perPageLov'] = $perPageLov;
        $this->paginationDataLov[$key]['currentPageLov'] = 1; // Reset halaman ke 1
    }

    /**
     * Memperbarui query pencarian dan reset halaman ke 1.
     */
    public function updatedSearchLov($key)
    {
        $this->paginationDataLov[$key]['currentPageLov'] = 1;
    }

    public function updatedPaginationDataLov($value, $key)
    {
        if (str_ends_with($key, 'searchLov')) {
            $segments = explode('.', $key);
            $dataKey = $segments[0];
            $this->reloadPageLov($dataKey); // Reset halaman jika pencarian diperbarui
        }
    }



    /**
     * Menyetel total data untuk key tertentu.
     * @param int $total
     * @param string $key
     */
    public function setTotalLov($total, $key)
    {
        $this->paginationDataLov[$key]['totalLov'] = $total;
    }

    /**
     * Mendapatkan data pagination untuk key tertentu.
     * @param string $key
     * @return array
     */
    public function getPaginationDataLov($key)
    {
        return $this->paginationDataLov[$key];
    }
}
