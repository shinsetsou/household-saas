<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Stringable;

class Seo implements Arrayable, Stringable
{
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $image,
        public string $siteName,
        public string $type = 'website',
        public ?string $locale = null,
        public bool $noIndex = false,
        public array $schema = [],
    ) {}

    public static function make(
        ?string $title = null,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
        ?string $siteName = null,
        ?string $type = null,
        ?bool $noIndex = null,
        array $schema = [],
    ): self {
        $siteName ??= (string) config('app.name', 'BarangayBooks');

        $baseTitle = $title !== null && $title !== ''
            ? Str::limit($title, 55, '')
            : $siteName;

        $suffix = ' — '.$siteName;
        if (! Str::contains(Str::lower($baseTitle), Str::lower($siteName))) {
            $resolvedTitle = Str::limit($baseTitle.$suffix, 60, '');
        } else {
            $resolvedTitle = Str::limit($baseTitle, 60, '');
        }

        $resolvedDescription = $description
            ? Str::limit($description, 160, '')
            : 'BarangayBooks is the modern ledger for Philippine homeowners associations: photo-verified payments, instant delinquency reports, and audit-ready exports.';

        $resolvedCanonical = $canonical ?: url()->current();

        $resolvedImage = $image ?: asset('og/default.png');

        $resolvedType ??= 'website';

        $resolvedNoIndex = $noIndex ?? false;

        $resolvedLocale ??= str_replace('_', '-', (string) app()->getLocale());

        return new self(
            title: $resolvedTitle,
            description: $resolvedDescription,
            canonical: $resolvedCanonical,
            image: $resolvedImage,
            siteName: $siteName,
            type: $resolvedType,
            locale: $resolvedLocale,
            noIndex: $resolvedNoIndex,
            schema: $schema,
        );
    }

    public function withSchema(array|self $schema): self
    {
        if ($schema instanceof self) {
            $schema = $schema->schema;
        }

        return new self(
            title: $this->title,
            description: $this->description,
            canonical: $this->canonical,
            image: $this->image,
            siteName: $this->siteName,
            type: $this->type,
            locale: $this->locale,
            noIndex: $this->noIndex,
            schema: array_merge($this->schema, $schema),
        );
    }

    public function withNoIndex(bool $noIndex = true): self
    {
        return new self(
            title: $this->title,
            description: $this->description,
            canonical: $this->canonical,
            image: $this->image,
            siteName: $this->siteName,
            type: $this->type,
            locale: $this->locale,
            noIndex: $noIndex,
            schema: $this->schema,
        );
    }

    public function shouldNoIndex(): bool
    {
        return $this->noIndex;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'image' => $this->image,
            'site_name' => $this->siteName,
            'type' => $this->type,
            'locale' => $this->locale,
            'noindex' => $this->noIndex,
            'schema' => $this->schema,
        ];
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        $html = [];

        $html[] = '<meta name="description" content="'.e($this->description).'">';

        if ($this->noIndex) {
            $html[] = '<meta name="robots" content="noindex, nofollow">';
            $html[] = '<meta name="googlebot" content="noindex, nofollow">';
        } else {
            $html[] = '<link rel="canonical" href="'.e($this->canonical).'">';
        }

        $ogTitle = Str::limit($this->title, 95, '');

        $html[] = '<meta property="og:site_name" content="'.e($this->siteName).'">';
        $html[] = '<meta property="og:type" content="'.e($this->type).'">';
        $html[] = '<meta property="og:title" content="'.e($ogTitle).'">';
        $html[] = '<meta property="og:description" content="'.e($this->description).'">';
        $html[] = '<meta property="og:url" content="'.e($this->canonical).'">';
        $html[] = '<meta property="og:image" content="'.e($this->image).'">';
        $html[] = '<meta property="og:image:width" content="1200">';
        $html[] = '<meta property="og:image:height" content="630">';
        if ($this->locale) {
            $html[] = '<meta property="og:locale" content="'.e($this->locale).'">';
        }

        $html[] = '<meta name="twitter:card" content="summary_large_image">';
        $html[] = '<meta name="twitter:title" content="'.e($ogTitle).'">';
        $html[] = '<meta name="twitter:description" content="'.e($this->description).'">';
        $html[] = '<meta name="twitter:image" content="'.e($this->image).'">';
        $html[] = '<meta name="twitter:site" content="@barangaybooks">';

        foreach ($this->schema as $node) {
            $html[] = '<script type="application/ld+json">'.json_encode($node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>';
        }

        return implode("\n    ", $html);
    }
}
