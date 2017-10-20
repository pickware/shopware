<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * @category  Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Tests_Controllers_Backend_ConfigTest extends Enlight_Components_Test_Controller_TestCase
{
    /**
     * tests the cron job config pagination
     */
    public function testCronJobPaginationConfig()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        $this->checkTableListConfig('cronJob');

        $this->reset();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        $this->checkGetTableListConfigPagination('cronJob');
    }

    /**
     * tests the cron job search
     */
    public function testCronJobSearchConfig()
    {
        $sql = 'SELECT count(*) FROM  s_crontab';
        $totalCronJobCount = Shopware()->Db()->fetchOne($sql);

        //test the search
        $this->checkGetTableListSearch('a', $totalCronJobCount, 'cronJob');

        $this->reset();

        //test the search with a pagination
        $this->checkGetTableListSearchWithPagination('a', 'cronJob');
    }

    /**
     * tests the searchField config pagination
     */
    public function testSearchFieldConfig()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        $this->checkTableListConfig('searchField');

        $this->reset();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        $this->checkGetTableListConfigPagination('searchField');
    }

    /**
     * tests the cron job search
     */
    public function testSearchFieldSearchConfig()
    {
        $sql = 'SELECT count(*)
                FROM s_search_fields f
                LEFT JOIN s_search_tables t on f.tableID = t.id';
        $totalCronJobCount = Shopware()->Db()->fetchOne($sql);

        $this->checkGetTableListSearch('b', $totalCronJobCount, 'searchField');

        $this->reset();

        $this->checkGetTableListSearchWithPagination('b', 'searchField');
    }

    /**
     * tests whether the list of pdf documents includes its translations
     */
    public function testIfPDFDocumentsListIncludesTranslation()
    {
        // set up
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth(false);
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();

        // login
        $this->Request()->setMethod('POST');
        $this->Request()->setPost([
            'username' => 'demo',
            'password' => 'demo',
        ]);
        $this->dispatch('backend/Login/login');

        $getParams = [
            '_repositoryClass' => 'document',
            '_dc' => '1234567890',
            'page' => '1',
            'start' => '0',
            'limit' => '20',
        ];

        $this->reset();

        // Check if German values are still the same
        $this->Request()->setMethod('GET');
        $getString = http_build_query($getParams);
        $response = $this->dispatch('backend/Config/getList?' . $getString);

        $responseJSON = json_decode($response->getBody(), true);
        $this->assertEquals(true, $responseJSON['success']);

        foreach ($responseJSON['data'] as $documentType) {
            $this->assertEquals($documentType['name'], $documentType['description']);
        }

        $this->reset();
        Shopware()->Container()->reset('translation');

        // Check for English translations
        $user = Shopware()->Container()->get('Auth')->getIdentity();
        $user->locale = Shopware()->Models()->getRepository(
            'Shopware\Models\Shop\Locale'
        )->find(2);

        $this->Request()->setMethod('GET');
        $getString = http_build_query($getParams);
        $response = $this->dispatch('backend/Config/getList?' . $getString);

        $responseJSON = json_decode($response->getBody(), true);
        $this->assertEquals(true, $responseJSON['success']);

        foreach ($responseJSON['data'] as $documentType) {
            switch ($documentType['id']) {
                case 1:
                    $this->assertEquals('Invoice', $documentType['description']);
                    break;
                case 2:
                    $this->assertEquals('Notice of delivery', $documentType['description']);
                    break;
                case 3:
                    $this->assertEquals('Credit', $documentType['description']);
                    break;
                case 4:
                    $this->assertEquals('Cancellation', $documentType['description']);
                    break;
            }
        }
    }

    /**
     * test the config tableList
     *
     * @param $tableListName
     */
    private function checkTableListConfig($tableListName)
    {
        // should return more than 2 items
        $this->Request()->setMethod('GET');
        $this->dispatch('backend/Config/getTableList/_repositoryClass/' . $tableListName);
        $returnData = $this->View()->getAssign('data');
        $this->assertGreaterThan(2, count($returnData));
        $this->assertTrue($this->View()->getAssign('success'));
    }

    /**
     * test the config table list with pagination
     *
     * @param $tableListName
     */
    private function checkGetTableListConfigPagination($tableListName)
    {
        $this->Request()->setMethod('GET');
        $this->dispatch('backend/Config/getTableList/_repositoryClass/' . $tableListName . '?page=1&start=0&limit=2');
        $this->assertTrue($this->View()->getAssign('success'));
        $returnData = $this->View()->getAssign('data');
        $this->assertGreaterThan(2, $this->View()->getAssign('total'));
        $this->assertCount(2, $returnData);
    }

    /**
     * checks the search of the table list config
     *
     * @param $searchTerm
     * @param $totalCount
     * @param $tableListName
     */
    private function checkGetTableListSearch($searchTerm, $totalCount, $tableListName)
    {
        $queryParams = [
            'page' => '1',
            'start' => '0',
            'limit' => 25,
            'filter' => json_encode(
                [
                    [
                        'property' => 'name',
                        'value' => '%' . $searchTerm . '%',
                    ],
                ]
            ),
        ];
        $query = http_build_query($queryParams);
        $url = 'backend/Config/getTableList/_repositoryClass/' . $tableListName . '?';
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        $this->dispatch($url . $query);
        $returnData = $this->View()->getAssign('data');
        $this->assertGreaterThan(0, count($returnData));
        $this->assertLessThan($totalCount, count($returnData));
        $this->assertTrue($this->View()->getAssign('success'));
    }

    /**
     *  checks the search and the pagination of the table list config
     *
     * @param $searchTerm
     * @param $tableListName
     */
    private function checkGetTableListSearchWithPagination($searchTerm, $tableListName)
    {
        $queryParams = [
            'page' => '1',
            'start' => '0',
            'limit' => 2,
            'filter' => json_encode(
                [
                    [
                        'property' => 'name',
                        'value' => '%' . $searchTerm . '%',
                    ],
                ]
            ),
        ];

        $query = http_build_query($queryParams);
        $url = 'backend/Config/getTableList/_repositoryClass/' . $tableListName . '?';
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        $this->dispatch($url . $query);
        $returnData = $this->View()->getAssign('data');
        $this->assertCount(2, $returnData);
        $this->assertTrue($this->View()->getAssign('success'));
    }
}
