<?php

namespace App\Tenancy;

class TenantContext
{
    private ?int $empresaId = null;

    public function empresaId(): ?int
    {
        return $this->empresaId;
    }

    public function setEmpresaId(?int $empresaId): void
    {
        $this->empresaId = $empresaId;
    }

    public function clear(): void
    {
        $this->empresaId = null;
    }
}
