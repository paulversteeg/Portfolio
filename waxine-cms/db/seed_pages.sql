-- Optional: seed the static "About" page from the original about.php.
-- Load after schema.sql:  mysql -u USER -p waxine < db/seed_pages.sql
-- Edit later in the admin, or directly here. Talks/Publications left as
-- stubs to fill in once you locate that content.

INSERT INTO pages (title, slug, subtitle, body_html, nav_order, in_nav) VALUES
('About', 'about', 'me as a designer',
'<p>I am a <i>UX Lead</i> working at <a href="http://www.info.nl">Info</a>. As a means to focus my thoughts I started writing (december 24th 2014). As <a href="https://zenoemenhetdesignthinking.wordpress.com/tag/cor-noltee/">Cor Noltee</a> and <a href="https://www.garyvaynerchuk.com">Gary Vee</a> as my inspiration I will write down my thoughts about the web, Interaction Design and User Experience.</p>
<p>My mission is to challenge user centered design with the dynamic variables of the web. In our ever-changing online world it’s important to stay on top of our game by making our designs relevant for our users. This is the reason we need to include personal context into our design routine. My ongoing research on the topic of Dynamic Design is important for making the online experience successful. The last few years, my work gradually became more focussed on making the interface smart and flexible to extend someones personal context. My goal is to “Get real” as quick as possible and to focus on the primary user goals. Getting a understanding of what the user actually wants, is critical for making your design successful. Design thinking is therefore an invaluable asset when creating an online concept.</p>
<p>I hold a Bachelor of Science degree in Interaction Design and have over a decade of experience as a designer and consultant. I’ve worked for clients in several areas and this allows me to take a broader view upon the craft of user experience design.</p>
<h2>Contact details:</h2>
<p class="address"><address>Paul Versteeg<br><a href="mailto:paul@waxine.nl?subject=Get in touch">paul@waxine.nl</a></address></p>
<h2>Other channels:</h2>
<ul>
<li><a href="https://www.linkedin.com/in/paulversteeg">LinkedIn</a></li>
<li><a href="https://vimeo.com/paulversteeg">Vimeo</a></li>
</ul>',
1, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);
