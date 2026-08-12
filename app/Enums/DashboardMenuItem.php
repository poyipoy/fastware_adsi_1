<?php

namespace App\Enums;

enum DashboardMenuItem: string
{
    case KELOLA_AKUN = 'kelola_akun';
    case KELOLA_CUSTOMER = 'kelola_customer';
    case MAINTENANCE = 'maintenance';
    case HANDLING_KLAIM = 'handling_klaim';
    case PEOPLE_DEVELOPMENT = 'people_development';
    case SUMBANG_SARAN = 'sumbang_saran';
    case KNOWLEDGE_MANAGEMENT = 'knowledge_management';
    case PENGAJUAN_BARANG = 'pengajuan_barang';
    case DASHBOARD_TCPD = 'dashboard_tcpd';
    case DASHBOARD_BOPM = 'dashboard_bopm';

    /**
     * Get menu item label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::KELOLA_AKUN => 'Akun Users',
            self::KELOLA_CUSTOMER => 'Customers',
            self::MAINTENANCE => 'Maintenance',
            self::HANDLING_KLAIM => 'Handling Klaim dan Komplain',
            self::PEOPLE_DEVELOPMENT => 'People Development',
            self::SUMBANG_SARAN => 'Sumbang Saran',
            self::KNOWLEDGE_MANAGEMENT => 'Knowledge Management',
            self::PENGAJUAN_BARANG => 'Pengajuan Barang',
            self::DASHBOARD_TCPD => 'Dashboard TCPD',
            self::DASHBOARD_BOPM => 'Dashboard BOPM',
        };
    }

    /**
     * Get menu item route name
     * 
     * @return string
     */
    public function getRoute(): string
    {
        return match($this) {
            self::KELOLA_AKUN => 'dashboardusers',
            self::KELOLA_CUSTOMER => 'dashboardcustomers',
            self::MAINTENANCE => 'dashboardMaintenance',
            self::HANDLING_KLAIM => 'dshandling',
            self::PEOPLE_DEVELOPMENT => 'dsCompetency',
            self::SUMBANG_SARAN => 'dashboardSS',
            self::KNOWLEDGE_MANAGEMENT => 'dsKnowlege',
            self::PENGAJUAN_BARANG => 'dashboardFPB',
            self::DASHBOARD_TCPD => 'dashboardTCPD',
            self::DASHBOARD_BOPM => 'bopm.dashboard.index',
        };
    }

    /**
     * Get required access group for this menu item
     * 
     * @return DashboardMenuAccessGroup
     */
    public function getAccessGroup(): DashboardMenuAccessGroup
    {
        return match($this) {
            self::KELOLA_AKUN, self::KELOLA_CUSTOMER => DashboardMenuAccessGroup::KELOLA_DATA,
            self::MAINTENANCE => DashboardMenuAccessGroup::DASHBOARD_MAINTENANCE,
            self::HANDLING_KLAIM => DashboardMenuAccessGroup::DASHBOARD_HANDLING,
            self::PEOPLE_DEVELOPMENT => DashboardMenuAccessGroup::DASHBOARD_COMPETENCY,
            self::SUMBANG_SARAN => DashboardMenuAccessGroup::DASHBOARD_SS,
            self::KNOWLEDGE_MANAGEMENT => DashboardMenuAccessGroup::DASHBOARD_KNOWLEDGE,
            self::PENGAJUAN_BARANG => DashboardMenuAccessGroup::DASHBOARD_FPB,
            self::DASHBOARD_TCPD => DashboardMenuAccessGroup::DASHBOARD_TCPD,
            self::DASHBOARD_BOPM => DashboardMenuAccessGroup::DASHBOARD_BOPM,
        };
    }

    /**
     * Get menu item data as array
     * 
     * @param int|null $roleId
     * @param string $userName
     * @return array
     */
    public function toArray(?int $roleId, string $userName): array
    {
        // Menu ini dirender oleh composer hanya untuk pengguna terautentikasi.
        if ($this === self::KNOWLEDGE_MANAGEMENT) {
            return [
                'key' => $this->value,
                'label' => $this->getLabel(),
                'route' => $this->getRoute(),
                'visible' => true,
            ];
        }

        $accessGroup = $this->getAccessGroup();
        $hasAccess = $accessGroup->hasAccess($roleId, $userName);

        return [
            'key' => $this->value,
            'label' => $this->getLabel(),
            'route' => $this->getRoute(),
            'visible' => $hasAccess,
        ];
    }
}
