<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAndCalculatorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_page_renders_with_faq_page_schema_json_ld(): void
    {
        $response = $this->get(route('content.faq'));

        $response->assertOk();
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('bulk tiered pricing', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type": "FAQPage"', false);
        $response->assertSee('acceptedAnswer', false);
    }

    public function test_material_calculators_page_renders_successfully(): void
    {
        $response = $this->get(route('content.calculators'));

        $response->assertOk();
        $response->assertSee('Material Quantity Estimators');
        $response->assertSee('RCC Slab Concrete');
        $response->assertSee('Brickwork Masonry');
        $response->assertSee('Wall Paint Coverage');
    }

    public function test_knowledge_hub_index_and_article_detail_render_successfully(): void
    {
        // Index
        $indexResponse = $this->get(route('content.knowledge.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Construction & Material Knowledge Hub', false);
        $indexResponse->assertSee('Understanding Cement Grades: OPC 53 vs. PPC');

        // Article Detail
        $articleResponse = $this->get(route('content.knowledge.article', 'understanding-cement-grades-opc-vs-ppc'));
        $articleResponse->assertOk();
        $articleResponse->assertSee('Ordinary Portland Cement (OPC 53)');
        $articleResponse->assertSee('Portland Pozzolana Cement (PPC)');
        $articleResponse->assertSee('application/ld+json', false);
        $articleResponse->assertSee('"@type": "TechArticle"', false);

        // Non-existent article returns 404
        $notFoundResponse = $this->get(route('content.knowledge.article', 'non-existent-article-slug'));
        $notFoundResponse->assertNotFound();
    }

    public function test_about_page_renders_successfully(): void
    {
        $response = $this->get(route('content.about'));

        $response->assertOk();
        $response->assertSee('The Modern Universal Commerce Engine');
        $response->assertSee('Domain-Neutral Core');
        $response->assertSee('Pessimistic Inventory Safety');
        $response->assertSee('Server-Authoritative Pricing');
    }

    public function test_contact_form_submission_validates_and_redirects(): void
    {
        $getResponse = $this->get(route('content.contact'));
        $getResponse->assertOk();
        $getResponse->assertSee('Support & Procurement Desk', false);

        // Missing fields validation
        $invalidResponse = $this->post(route('content.contact.submit'), []);
        $invalidResponse->assertSessionHasErrors(['name', 'email', 'phone', 'subject', 'message']);

        // Valid submission
        $validResponse = $this->post(route('content.contact.submit'), [
            'name' => 'Karan Mehta',
            'email' => 'karan@metroinfra.test',
            'phone' => '+91 9900112233',
            'subject' => 'Fly Ash Brick Procurement 50,000 Nos',
            'message' => 'Need quotation and delivery schedule for commercial project site in Whitefield.',
        ]);

        $validResponse->assertRedirect();
        $validResponse->assertSessionHas('success');
    }

    public function test_sitemap_includes_content_and_knowledge_urls(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('/faq', $content);
        $this->assertStringContainsString('/about', $content);
        $this->assertStringContainsString('/contact', $content);
        $this->assertStringContainsString('/calculators', $content);
        $this->assertStringContainsString('/knowledge', $content);
        $this->assertStringContainsString('understanding-cement-grades-opc-vs-ppc', $content);
    }
}
