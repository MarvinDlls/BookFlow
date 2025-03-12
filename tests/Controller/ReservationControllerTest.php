<?php

namespace App\Tests\Controller;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $reservationRepository;
    private string $path = '/reservation/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->reservationRepository = $this->manager->getRepository(Reservation::class);

        foreach ($this->reservationRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Reservation index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'reservation[reservation_date]' => 'Testing',
            'reservation[status]' => 'Testing',
            'reservation[expiration_date]' => 'Testing',
            'reservation[created_at]' => 'Testing',
            'reservation[updated_at]' => 'Testing',
            'reservation[user]' => 'Testing',
            'reservation[book]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->reservationRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Reservation();
        $fixture->setReservation_date('My Title');
        $fixture->setStatus('My Title');
        $fixture->setExpiration_date('My Title');
        $fixture->setCreated_at('My Title');
        $fixture->setUpdated_at('My Title');
        $fixture->setUser('My Title');
        $fixture->setBook('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Reservation');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Reservation();
        $fixture->setReservation_date('Value');
        $fixture->setStatus('Value');
        $fixture->setExpiration_date('Value');
        $fixture->setCreated_at('Value');
        $fixture->setUpdated_at('Value');
        $fixture->setUser('Value');
        $fixture->setBook('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'reservation[reservation_date]' => 'Something New',
            'reservation[status]' => 'Something New',
            'reservation[expiration_date]' => 'Something New',
            'reservation[created_at]' => 'Something New',
            'reservation[updated_at]' => 'Something New',
            'reservation[user]' => 'Something New',
            'reservation[book]' => 'Something New',
        ]);

        self::assertResponseRedirects('/reservation/');

        $fixture = $this->reservationRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getReservation_date());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getExpiration_date());
        self::assertSame('Something New', $fixture[0]->getCreated_at());
        self::assertSame('Something New', $fixture[0]->getUpdated_at());
        self::assertSame('Something New', $fixture[0]->getUser());
        self::assertSame('Something New', $fixture[0]->getBook());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Reservation();
        $fixture->setReservation_date('Value');
        $fixture->setStatus('Value');
        $fixture->setExpiration_date('Value');
        $fixture->setCreated_at('Value');
        $fixture->setUpdated_at('Value');
        $fixture->setUser('Value');
        $fixture->setBook('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/reservation/');
        self::assertSame(0, $this->reservationRepository->count([]));
    }
}
