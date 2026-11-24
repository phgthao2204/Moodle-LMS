<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_meko_competencies
// @copyright  2026 Dự án ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_meko_competencies;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical ACCEES Digital Competency Frameworks Database Synchronizer.
 * Upserts DIGCOMP_ACCEES_STUDENTS (5 domains, 18 competencies, 72 levels) and
 * DIGCOMP_ACCEES_FACULTY (8 domains, 28 competencies, 84 levels) directly in Moodle DB
 * while preserving existing competency primary keys and course linkages.
 */
class framework_sync {

    /**
     * Return canonical standardized competency frameworks definition.
     *
     * @return array
     */
    public static function get_canonical_frameworks(): array {
        return [
            [
                'idnumber' => 'DIGCOMP_ACCEES_STUDENTS',
                'shortname' => 'Students Digital Competency Framework (DigComp 2.2)',
                'description' => '<p>Operational Development Plan for Students\' Digital Competences in Vietnamese Higher Education. Developed by the ACCEES Consortium (Erasmus+ CBHE-101179860) under the supervision of the University of Strasbourg. Comprises 5 domains, 18 specific competences, and 4 proficiency levels (N1 Basic to N4 In-depth).</p>',
                'domains' => [
                    [
                        'idnumber' => 'D1',
                        'shortname' => 'D1. Digital information and data',
                        'description' => '<p>Searching, evaluating the reliability of, and storing, managing, and retrieving data and digital content for academic study and research.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'SV_C1_1',
                                'shortname' => 'C1.1. Searching for information and data',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C1_1_N1',
                                        'shortname' => 'C1.1 [Level N1 - Basic]: Searching for information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Carry out simple searches using single keywords in search engines, usually by following instructions. Rely on default results without filtering or checking their relevance.</p><p><strong>Practical Examples:</strong></p><ul><li>Use simple, single keywords to carry out a search and open the first results suggested.</li><li>Use the university’s homepage to find basic information about courses.</li><li>Follow the lecturer’s instructions step by step without adapting the search process.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_1_N2',
                                        'shortname' => 'C1.1 [Level N2 - Intermediate]: Searching for information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Conducting searches independently, selecting appropriate keywords and using available filters (date, file type, source). Finding information that meets their personal needs.</p><p><strong>Practical Examples:</strong></p><ul><li>Select appropriate keywords and combine them in search engines.</li><li>Apply filters (date, file type, source) to refine results.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_1_N3',
                                        'shortname' => 'C1.1 [Level N3 - Advanced]: Searching for information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Apply advanced search techniques (Boolean operators, quotation marks, database queries, AI tools) across multiple platforms. Be able to guide others in conducting effective searches and adapt their strategies to different contexts.</p><p><strong>Practical Examples:</strong></p><ul><li>Use Boolean operators and quotation marks to refine results.</li><li>Search academic databases (Google Scholar, Scopus) and institutional repositories.</li><li>Compare results across different sources and explain to peers how to improve their own searches.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_1_N4',
                                        'shortname' => 'C1.1 [Level N4 - In-depth]: Searching for information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Design and optimise search strategies within specialist databases, on AI-powered platforms and within professional networks. Be able to integrate search systems into team or institutional practices, producing curated collections and ensuring long-term access to information.</p><p><strong>Practical Examples:</strong></p><ul><li>Develop a multi-stage search strategy using databases, AI tools and newsletters.</li><li>Create and share a structured reference library (Zotero, Mendeley) using tagging systems.</li><li>Provide guidelines or organise training sessions for peers on systematic research and information monitoring.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C1_2',
                                'shortname' => 'C1.2. Evaluating information and data',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C1_2_N1',
                                        'shortname' => 'C1.2 [Level N1 - Basic]: Evaluating information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Recognise obvious indicators of reliability (e.g. a reputable website, the author’s name), but rely mainly on guidance. Limited autonomy, often accepting information without checking it thoroughly.</p><p><strong>Practical Examples:</strong></p><ul><li>Check whether the source mentions the author’s name and the publication date.</li><li>Follow the teacher’s advice without questioning the credibility of other sources.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_2_N2',
                                        'shortname' => 'C1.2 [Level N2 - Intermediate]: Evaluating information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Able to independently check basic reliability criteria (author, publication date, source) and compare simple sources. Applying knowledge to meet personal needs in the context of routine academic tasks.</p><p><strong>Practical Examples:</strong></p><ul><li>Compare two different articles to check their consistency.</li><li>Check the date of the last update to the content.</li><li>Discard sources where the author or references are not clearly indicated.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_2_N3',
                                        'shortname' => 'C1.2 [Level N3 - Advanced]: Evaluating information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Apply critical analysis to diverse and complex contexts. Be able to detect bias, cross-check multiple sources and guide peers in evaluating information. Use high-level reasoning to select the most reliable sources.</p><p><strong>Practical Examples:</strong></p><ul><li>Identify potential biases in a news article compared to an academic article.</li><li>Cross-check data from several databases before citing it.</li><li>Explain to classmates why one report is more credible than another.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_2_N4',
                                        'shortname' => 'C1.2 [Level N4 - In-depth]: Evaluating information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Students are able to create systematic evaluation frameworks, resolve complex cases of misinformation and contribute to academic or professional communities. They do not merely evaluate, but also lead and guide others, developing standards that promote reliable digital practices.</p><p><strong>Practical Examples:</strong></p><ul><li>Design a checklist or evaluation grid to assess academic sources.</li><li>Train peers to identify fake news and disinformation.</li><li>Contribute to a shared institutional database of validated sources.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C1_3',
                                'shortname' => 'C1.3. Manage information and data',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C1_3_N1',
                                        'shortname' => 'C1.3 [Level N1 - Basic]: Manage information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Save files in default locations or on their personal devices, mainly with guidance. Limited autonomy, often needing help to retrieve data.</p><p><strong>Practical Examples:</strong></p><ul><li>Save homework to the desktop without creating folders.</li><li>Store files only on a USB stick or a personal laptop.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_3_N2',
                                        'shortname' => 'C1.3 [Level N2 - Intermediate]: Manage information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Store and organise files independently using basic structures (folders, file names, cloud storage). Be able to retrieve information for personal learning tasks.</p><p><strong>Practical Examples:</strong></p><ul><li>Create folders by subject or by course.</li><li>Use relevant file names (e.g. ‘Essay_History2025.docx’).</li><li>Upload files to Google Drive/OneDrive to access them from any device.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_3_N3',
                                        'shortname' => 'C1.3 [Level N3 - Advanced]: Manage information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Apply systematic organisational methods across multiple platforms. Be able to assist peers by introducing them to effective tools, ensuring accessibility and version control.</p><p><strong>Practical Examples:</strong></p><ul><li>Use a database or reference manager (Zotero, Mendeley) to organise research articles.</li><li>Use cloud-based collaboration tools (e.g. shared Drive, Dropbox) with appropriate access rights.</li><li>Explain to classmates how to structure folders for a group project.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C1_3_N4',
                                        'shortname' => 'C1.3 [Level N4 - In-depth]: Manage information and data',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Design and integrate advanced data management systems into professional or academic practice. Demonstrate innovation and contribute to sustainable digital organisation within communities.</p><p><strong>Practical Examples:</strong></p><ul><li>Develop a shared file taxonomy and metadata system for a research group.</li><li>Implement backup and preservation strategies for long-term projects.</li><li>Contribute to institutional data management policies or digital repositories.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D2',
                        'shortname' => 'D2. Digital communication and collaboration',
                        'description' => '<p>Interacting, sharing resources, collaborating in groups, and expressing one\'s digital identity and netiquette in social and academic environments.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'SV_C2_1',
                                'shortname' => 'C2.1. Interacting',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C2_1_N1',
                                        'shortname' => 'C2.1 [Level N1 - Basic]: Interacting',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Use digital tools in a basic way to communicate.</p><p><strong>Practical Examples:</strong></p><ul><li>Send a simple email.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_1_N2',
                                        'shortname' => 'C2.1 [Level N2 - Intermediate]: Interacting',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Take part in online exchanges in familiar contexts.</p><p><strong>Practical Examples:</strong></p><ul><li>Reply in a group chat.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_1_N3',
                                        'shortname' => 'C2.1 [Level N3 - Advanced]: Interacting',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Actively contribute to structured discussions with small groups.</p><p><strong>Practical Examples:</strong></p><ul><li>Organise an online meeting.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_1_N4',
                                        'shortname' => 'C2.1 [Level N4 - In-depth]: Interacting',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Lead, guide and facilitate digital interactions in a variety of contexts.</p><p><strong>Practical Examples:</strong></p><ul><li>Lead a remote team meeting.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C2_2',
                                'shortname' => 'C2.2. Share and publish',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C2_2_N1',
                                        'shortname' => 'C2.2 [Level N1 - Basic]: Share and publish',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Publish or share short pieces of information using simple tools, but requires precise instructions.</p><p><strong>Practical Examples:</strong></p><ul><li>Post a short message on a learning platform following the provided template.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_2_N2',
                                        'shortname' => 'C2.2 [Level N2 - Intermediate]: Share and publish',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Publish and comment independently on well-known platforms, but in a style that is still basic.</p><p><strong>Practical Examples:</strong></p><ul><li>Post a personal reflection on a course forum or share an article on social media.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_2_N3',
                                        'shortname' => 'C2.2 [Level N3 - Advanced]: Share and publish',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Choose the appropriate format and tool, organise the information, and engage constructively with the audience.</p><p><strong>Practical Examples:</strong></p><ul><li>Write a blog post to explain an idea and respond to comments.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_2_N4',
                                        'shortname' => 'C2.2 [Level N4 - In-depth]: Share and publish',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Manage a communication channel, promote one’s own content as well as that of others, whilst adhering to ethical and professional standards.</p><p><strong>Practical Examples:</strong></p><ul><li>Administer a CMS site or a professional webpage, create an online promotional campaign for a team project.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C2_3',
                                'shortname' => 'C2.3. Collaborate',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C2_3_N1',
                                        'shortname' => 'C2.3 [Level N1 - Basic]: Collaborate',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Join an online group, reply to messages or share a document according to instructions.</p><p><strong>Practical Examples:</strong></p><ul><li>Answer a question in an LMS/WhatsApp group or send a requested file.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_3_N2',
                                        'shortname' => 'C2.3 [Level N2 - Intermediate]: Collaborate',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Communicate, share documents and carry out tasks with the group via an online collaboration tool.</p><p><strong>Practical Examples:</strong></p><ul><li>Share a document on Google Drive and discuss the content with the group.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_3_N3',
                                        'shortname' => 'C2.3 [Level N3 - Advanced]: Collaborate',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Co-edit documents, use version control tools, and resolve conflicts within the group effectively.</p><p><strong>Practical Examples:</strong></p><ul><li>Co-author a report on Google Docs, using the track changes and comment features.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_3_N4',
                                        'shortname' => 'C2.3 [Level N4 - In-depth]: Collaborate',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Organise, allocate tasks, track progress, and fully manage a collaborative project on a digital platform.</p><p><strong>Practical Examples:</strong></p><ul><li>Manage a project on Trello/Notion, assign tasks and integrate resources from various collaborative tools.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C2_4',
                                'shortname' => 'C2.4. Getting started in the digital world',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C2_4_N1',
                                        'shortname' => 'C2.4 [Level N1 - Basic]: Getting started in the digital world',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Recognising and participating in a basic way in the digital environment.</p><p><strong>Practical Examples:</strong></p><ul><li>Create a personal profile on Facebook or LinkedIn with basic information.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_4_N2',
                                        'shortname' => 'C2.4 [Level N2 - Intermediate]: Getting started in the digital world',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Actively use digital platforms in familiar contexts.</p><p><strong>Practical Examples:</strong></p><ul><li>Take part in a discussion in a professional group on LinkedIn; share an article related to one’s field.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_4_N3',
                                        'shortname' => 'C2.4 [Level N3 - Advanced]: Getting started in the digital world',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Manage your personal image and express yourself in a focused and thoughtful manner.</p><p><strong>Practical Examples:</strong></p><ul><li>Write a professional blog, use LinkedIn to build a network and articulate one’s goals.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C2_4_N4',
                                        'shortname' => 'C2.4 [Level N4 - In-depth]: Getting started in the digital world',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Lead, influence and embody your values in the digital environment.</p><p><strong>Practical Examples:</strong></p><ul><li>Develop a personal brand across multiple platforms (LinkedIn, blog, forums), lead an online community and steer discussions.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D3',
                        'shortname' => 'D3. Creation and production of digital content',
                        'description' => '<p>Developing, editing, integrating multimedia digital content, applying copyright and licenses, and computer programming.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'SV_C3_1',
                                'shortname' => 'C3.1. Developing digital content',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C3_1_N1',
                                        'shortname' => 'C3.1 [Level N1 - Basic]: Developing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Create and edit digital content and express oneself through simple digital media (cameras, software, websites, AI tools, etc.)</p><p><strong>Practical Examples:</strong></p><ul><li>Design a poster or infographic to introduce yourself using online tools (Canva, PowerPoint, etc.)</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_1_N2',
                                        'shortname' => 'C3.1 [Level N2 - Intermediate]: Developing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Create and edit digital content in various formats. Express yourself through the creation of digital media</p><p><strong>Practical Examples:</strong></p><ul><li>Record a video to present yourself or share learning experiences, editing it using simple software (CapCut, iMovie, etc.) or publishing it on an e-learning platform.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_1_N3',
                                        'shortname' => 'C3.1 [Level N3 - Advanced]: Developing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Convert digital content into the most suitable formats. Express oneself by creating the most appropriate digital media.</p><p><strong>Practical Examples:</strong></p><ul><li>Create a multi-format research report: collect survey data, then present the data in tables and graphs (Excel), write an analysis report (Word/Google Docs) and a summary in the form of an infographic (Canva) or a slideshow for presentation in class.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_1_N4',
                                        'shortname' => 'C3.1 [Level N4 - In-depth]: Developing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Propose new ideas, processes and digital media solutions to create and publish digital content and express oneself through complex issues.</p><p><strong>Practical Examples:</strong></p><ul><li>Carry out a creative digital media project on social issues: design the content production process [analyse audience needs → choose the content format (video, podcast, infographic) → develop a publication plan across different platforms].</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C3_2',
                                'shortname' => 'C3.2. Integrating and editing digital content',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C3_2_N1',
                                        'shortname' => 'C3.2 [Level N1 - Basic]: Integrating and editing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Adjust content to ensure the accuracy of information in order to create new content and knowledge in a straightforward manner.</p><p><strong>Practical Examples:</strong></p><ul><li>A student’s long report can use AI to summarise the content to create concise and easy-to-understand information, and automatically check and correct spelling mistakes.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_2_N2',
                                        'shortname' => 'C3.2 [Level N2 - Intermediate]: Integrating and editing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Refine and improve the quality of information by adding examples, evidence and new data to create new content and insights with more detailed and specific information.</p><p><strong>Practical Examples:</strong></p><ul><li>Use AI to generate more examples, images, diagrams or multi-format summaries to make the information more accessible.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_2_N3',
                                        'shortname' => 'C3.2 [Level N3 - Advanced]: Integrating and editing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Integrate content to create new, unique, in-depth and practice-relevant content and information, rather than rewriting it.</p><p><strong>Practical Examples:</strong></p><ul><li>A teacher uploads several documents and then asks the AI to automatically compile personalised lessons for each group of students (for example, a group that needs to learn quickly, a group that needs plenty of examples)</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_2_N4',
                                        'shortname' => 'C3.2 [Level N4 - In-depth]: Integrating and editing digital content',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Using the integrated content, create new and unique knowledge, propose new ideas and processes for specific fields, predict trends and forecast the future.</p><p><strong>Practical Examples:</strong></p><ul><li>Synthesise large amounts of data, evidence and indicators to forecast the weather, analyse gold prices, etc.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C3_3',
                                'shortname' => 'C3.3. Enforcing copyright and licences',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C3_3_N1',
                                        'shortname' => 'C3.3 [Level N1 - Basic]: Enforcing copyright and licences',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Be aware of copyright, know how to cite sources, recognise certain basic licences and limit the use of content from unknown sources.</p><p><strong>Practical Examples:</strong></p><ul><li>Students writing essays: when they copy a passage from Wikipedia or an online newspaper, they must clearly cite the source in the reference list.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_3_N2',
                                        'shortname' => 'C3.3 [Level N2 - Intermediate]: Enforcing copyright and licences',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Correctly apply copyright regulations when integrating content editing, choose appropriate licences when reusing content, and manage the distinction between free, paid or conditional content.</p><p><strong>Practical Examples:</strong></p><ul><li>Students producing presentation videos: combine images from Wikimedia Commons, background music from YouTube’s free library and self-recorded videos of the group; at the end of the final product, clearly indicate the source and licence for each type of content.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_3_N3',
                                        'shortname' => 'C3.3 [Level N3 - Advanced]: Enforcing copyright and licences',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Create new digital content and proactively attach licences; guide and train others on the legal use of content; contribute to or develop policies and regulations on digital copyright and within organisations/communities; participate in the development of open content.</p><p><strong>Practical Examples:</strong></p><ul><li>Students create educational videos for the class and, once completed, attach a Creative Commons CC-BY licence so that others can use and modify them, provided the author is clearly credited.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_3_N4',
                                        'shortname' => 'C3.3 [Level N4 - In-depth]: Enforcing copyright and licences',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Create new digital content incorporating various copyright elements, and propose new ideas and processes.</p><p><strong>Practical Examples:</strong></p><ul><li>Students write a scientific report referencing the work of other authors, as well as their own initiatives and ideas.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C3_4',
                                'shortname' => 'C3.4. Programming',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C3_4_N1',
                                        'shortname' => 'C3.4 [Level N1 - Basic]: Programming',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Use programming languages to control computers with simple commands to perform specific tasks.</p><p><strong>Practical Examples:</strong></p><ul><li>Mathematics students use Python or R to perform statistical calculations on data tables.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_4_N2',
                                        'shortname' => 'C3.4 [Level N2 - Intermediate]: Programming',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Use a programming language to control the computer with a script (macro, function, scriptlet, etc.) to assist with personal work.</p><p><strong>Practical Examples:</strong></p><ul><li>Physics students use Python to simulate the free fall of an object.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_4_N3',
                                        'shortname' => 'C3.4 [Level N3 - Advanced]: Programming',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Use a programming language to control the computer using a complete programme (programme, services, etc.) for personal work.</p><p><strong>Practical Examples:</strong></p><ul><li>Computer science students use the Java programming language to write programmes that control network servers.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C3_4_N4',
                                        'shortname' => 'C3.4 [Level N4 - In-depth]: Programming',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Develop a comprehensive software solution to support the work of an organisation or an individual.</p><p><strong>Practical Examples:</strong></p><ul><li>Information technology students use the Java programming language and Spring Boot platforms to develop library management software.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D4',
                        'shortname' => 'D4. Digital security and ethics',
                        'description' => '<p>Protecting digital devices, personal data, privacy, physical and psychological health, and observing ethical netiquette in online interactions.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'SV_C4_1',
                                'shortname' => 'C4.1. Protecting digital devices, personal data and privacy',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C4_1_N1',
                                        'shortname' => 'C4.1 [Level N1 - Basic]: Protecting digital devices, personal data and privacy',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Under the supervision of others or with the help of a practical guide: recognise and distinguish issues relating to device security, personal data and privacy; recognise and distinguish threats and risks relating to device security, personal data and privacy; resolve issues relating to device security, personal data and privacy.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_1_N2',
                                        'shortname' => 'C4.1 [Level N2 - Intermediate]: Protecting digital devices, personal data and privacy',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Explain the threats and risks related to device security, personal data and privacy. Implement basic solutions for device security, personal data and privacy. Resolve issues related to device security, personal data and privacy.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_1_N3',
                                        'shortname' => 'C4.1 [Level N3 - Advanced]: Protecting digital devices, personal data and privacy',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Apply measures to protect devices, personal data and privacy. Train others in the protection of devices, personal data and privacy.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_1_N4',
                                        'shortname' => 'C4.1 [Level N4 - In-depth]: Protecting digital devices, personal data and privacy',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Propose and create new solutions for the protection of devices, personal data and privacy.</p>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C4_2',
                                'shortname' => 'C4.2. Protecting health, the environment and well-being',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C4_2_N1',
                                        'shortname' => 'C4.2 [Level N1 - Basic]: Protecting health, the environment and well-being',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Under the supervision of others or with the help of the guidance document: recognise and distinguish between health, environmental and digital security issues; recognise and distinguish between threats and risks related to health, the environment and digital security; resolve health, environmental and digital security issues.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_2_N2',
                                        'shortname' => 'C4.2 [Level N2 - Intermediate]: Protecting health, the environment and well-being',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Explain the threats and risks related to digital health, the environment and well-being. Implement solutions to protect digital health, the environment and well-being. Resolve problems related to digital health, the environment and well-being.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_2_N3',
                                        'shortname' => 'C4.2 [Level N3 - Advanced]: Protecting health, the environment and well-being',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Apply measures to protect digital health, the environment and digital security. Train others in the protection of digital health, the environment and digital security.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_2_N4',
                                        'shortname' => 'C4.2 [Level N4 - In-depth]: Protecting health, the environment and well-being',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Propose and create new solutions for the protection of digital health, the environment and digital safety.</p>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C4_3',
                                'shortname' => 'C4.3. Respecting netiquette',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C4_3_N1',
                                        'shortname' => 'C4.3 [Level N1 - Basic]: Respecting netiquette',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Under the supervision of others or with the aid of guidance documents: recognise the rules of conduct in the digital space; recognise and distinguish non-standard behaviour in the digital space; adopt standard behaviour in the digital space.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_3_N2',
                                        'shortname' => 'C4.3 [Level N2 - Intermediate]: Respecting netiquette',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Independently and at a basic level: understand the risks and consequences of non-standard behaviour in the digital environment; independently adopt standard behaviour in the digital environment; adapt non-standard behaviour so that it becomes standard in the digital environment.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_3_N3',
                                        'shortname' => 'C4.3 [Level N3 - Advanced]: Respecting netiquette',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Adopt standard behaviours in the digital space. Critique non-standard behaviours in the digital space. Teach others to adopt standard behaviours in the digital space.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C4_3_N4',
                                        'shortname' => 'C4.3 [Level N4 - In-depth]: Respecting netiquette',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Propose, create and drive trends in standard behaviour in the digital space.</p>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D5',
                        'shortname' => 'D5. Problem-solving using digital technology and artificial intelligence',
                        'description' => '<p>Identifying technological needs and solutions, using targeted tools, evaluating outcomes, and continuously improving digital solutions.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'SV_C5_1',
                                'shortname' => 'C5.1. Identifying technological needs and solutions',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C5_1_N1',
                                        'shortname' => 'C5.1 [Level N1 - Basic]: Identifying technological needs and solutions',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Identify simple technical problems and solutions when using digital equipment and environments under the guidance of others.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_1_N2',
                                        'shortname' => 'C5.1 [Level N2 - Intermediate]: Identifying technological needs and solutions',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Select appropriate solutions to independently resolve common technical problems when using digital equipment and environments.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_1_N3',
                                        'shortname' => 'C5.1 [Level N3 - Advanced]: Identifying technological needs and solutions',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Assess technical problems related to the use of digital equipment and environments and guide others in resolving these problems using a range of solutions.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_1_N4',
                                        'shortname' => 'C5.1 [Level N4 - In-depth]: Identifying technological needs and solutions',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Create solutions to complex technical problems related to the operation of equipment and the use of the digital environment; and propose new ideas and processes for specific areas.</p>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C5_2',
                                'shortname' => 'C5.2. Use targeted tools in the solutions adopted',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C5_2_N1',
                                        'shortname' => 'C5.2 [Level N1 - Basic]: Use targeted tools in the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Use digital technology according to instructions, relying heavily on others. Lack of clear planning, acting in a piecemeal manner, little or no self-assessment.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_2_N2',
                                        'shortname' => 'C5.2 [Level N2 - Intermediate]: Use targeted tools in the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Able to select and use digital tools in familiar situations. Intends to carry out tasks but lacks a systematic approach. Carries out actions according to basic procedures. Limited self-reflection.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_2_N3',
                                        'shortname' => 'C5.2 [Level N3 - Advanced]: Use targeted tools in the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Proactively applying a variety of digital tools to solve problems. Having clear plans and objectives, and a logical and flexible implementation process. Being able to analyse the strengths and weaknesses of one’s own approach.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_2_N4',
                                        'shortname' => 'C5.2 [Level N4 - In-depth]: Use targeted tools in the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Creative autonomy, combining digital technologies to form new solutions. Has a long-term strategy. Effective and sustainable actions. In-depth reflection. Able to adapt and improve regularly.</p>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C5_3',
                                'shortname' => 'C5.3. Evaluate the results of the solutions adopted',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C5_3_N1',
                                        'shortname' => 'C5.3 [Level N1 - Basic]: Evaluate the results of the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Carry out a preliminary assessment based on gut feeling, relying on external feedback, with little autonomy.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_3_N2',
                                        'shortname' => 'C5.3 [Level N2 - Intermediate]: Evaluate the results of the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Assess results in a reasonable and transparent manner in familiar contexts. Have the intention to improve, but without a specific plan. Begin to self-assess one’s own limitations.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_3_N3',
                                        'shortname' => 'C5.3 [Level N3 - Advanced]: Evaluate the results of the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Assess the relevance and sustainability of the solution. Proactively develop an adjustment plan. Logical and comprehensive evaluation process. Be able to analyse the pros and cons in depth.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_3_N4',
                                        'shortname' => 'C5.3 [Level N4 - In-depth]: Evaluate the results of the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Carrying out a strategic, independent and highly objective evaluation. Focusing on long-term improvement. A professional and effective evaluation process. In-depth reflection. Knowing how to learn from experience and share with others.</p>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'SV_C5_4',
                                'shortname' => 'C5.4. Improve the solutions adopted',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'SV_C5_4_N1',
                                        'shortname' => 'C5.4 [Level N1 - Basic]: Improve the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N1):</strong> Failure to recognise problems independently. Lack of self-discipline in improvement. Acting in a piecemeal manner, with little reflection.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_4_N2',
                                        'shortname' => 'C5.4 [Level N2 - Intermediate]: Improve the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N2):</strong> Beginning to identify areas for improvement. Having a basic intention to achieve objectives. Acting on instructions given. Demonstrating initial but limited reflection.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_4_N3',
                                        'shortname' => 'C5.4 [Level N3 - Advanced]: Improve the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N3):</strong> Proactively identifying alternative technologies and proposing improvements. Having a clear plan to improve efficiency. Flexible implementation process. Being able to self-assess, learn from experience and adapt.</p>',
                                    ],
                                    [
                                        'idnumber' => 'SV_C5_4_N4',
                                        'shortname' => 'C5.4 [Level N4 - In-depth]: Improve the solutions adopted',
                                        'description' => '<p><strong>Learning Outcome (Level N4):</strong> Be highly self-reliant and creative in proposing new solutions. Possess a broad vision, a long-term strategy, a systematic and professional approach, in-depth analysis, a commitment to continuous improvement, and a willingness to share experience.</p>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'idnumber' => 'DIGCOMP_ACCEES_FACULTY',
                'shortname' => 'Faculty Digital Competency Framework (DigCompEdu)',
                'description' => '<p>Operational Development Plan for Digital Competences of Lecturers and Support Staff in Higher Education. Developed by the ACCEES Consortium (Erasmus+ CBHE-101179860) under the supervision of the University of Mons and the University of Patras. Comprises 8 domains, 28 specific competences, and 3 proficiency levels (Basic, Intermediate, Advanced).</p>',
                'domains' => [
                    [
                        'idnumber' => 'DA',
                        'shortname' => 'A. Digital Environment',
                        'description' => '<p>Technical foundations: solving technical problems and establishing an effective digital work and teaching environment.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_A_1',
                                'shortname' => 'A.1. Solving technical problems',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_A_1_BASIC',
                                        'shortname' => 'A.1 [Level Basic]: Solving technical problems',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Identify and resolve common technical problems in the digital workplace.</p><p><strong>Practical Examples:</strong></p><ul><li>Example: resolving common minor issues on your computer, such as resetting your password, managing files or connecting devices.</li><li>Resolve minor software issues by restarting applications or performing basic updates.</li><li>If you encounter connectivity issues, check your network settings and restart your device to re-establish the connection.</li><li>For common device or software issues, search for answers on online forums or manufacturers’ help pages to resolve these issues yourself.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_A_1_INTERMEDIATE',
                                        'shortname' => 'A.1 [Level Intermediate]: Solving technical problems',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Mobilise specific resources to resolve technical difficulties specific to higher education and your area of expertise.</p><p><strong>Practical Examples:</strong></p><ul><li>First, consult the technical documentation available on the institution’s intranet to find solutions to common problems.</li><li>If you encounter technical difficulties integrating new digital tools into your lessons, consult your colleagues or specialist departments (such as the IT department) to find solutions tailored to your teaching needs.</li><li>Connect to the institution’s network from home (via a VPN [virtual private network]) or from a host institution (via the Ministry of Education or the national university).</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_A_1_ADVANCED',
                                        'shortname' => 'A.1 [Level Advanced]: Solving technical problems',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Excel at resolving advanced technical issues, anticipate challenges and contribute to the development of institutional practices.</p><p><strong>Practical Examples:</strong></p><ul><li>Anticipate potential compatibility issues with new software and operating systems used in the department or service and propose solutions before their deployment.</li><li>Actively participate in institutional working groups to propose technological innovations aimed at improving the digital experience for students and staff.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_A_2',
                                'shortname' => 'A.2. Setting up a digital working environment',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_A_2_BASIC',
                                        'shortname' => 'A.2 [Level Basic]: Setting up a digital working environment',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Install and configure basic digital tools for personal or professional use.</p>',
                                    ],
                                    [
                                        'idnumber' => 'GV_A_2_INTERMEDIATE',
                                        'shortname' => 'A.2 [Level Intermediate]: Setting up a digital working environment',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Set up digital environments specific to a professional or educational context; manage specialist software.</p><p><strong>Practical Examples:</strong></p><ul><li>Use e-learning platforms (such as Moodle, Teams, etc.) to set up courses, activities and interactions with students.</li><li>Use specialised educational software (such as Wooclap, Kahoot, Plickers, etc.) by configuring it to suit the needs of the courses being taught.</li><li>Deploy and manage collaborative solutions specific to one’s work environment, such as shared storage spaces or teamwork tools.</li><li>Connect to the institution’s network from home (via VPN) or from a host institution (via the Department for Education or a national university).</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_A_2_ADVANCED',
                                        'shortname' => 'A.2 [Level Advanced]: Setting up a digital working environment',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Design, improve and customise innovative digital environments; contribute to the implementation of institutional digital infrastructure.</p><p><strong>Practical Examples:</strong></p><ul><li>Participate in discussions on updates to learning platforms, integrating advanced and customised features to meet the specific needs of teachers and students.</li><li>Propose improvements to the institution’s digital infrastructure by introducing new educational technologies or enhancing existing tools to facilitate distance learning.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'DB',
                        'shortname' => 'B. Protection and Security',
                        'description' => '<p>Protecting digital devices, safeguarding personal data and privacy, and ensuring digital health, well-being, and environmental sustainability.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_B_1',
                                'shortname' => 'B.1. Protecting devices',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_B_1_BASIC',
                                        'shortname' => 'B.1 [Level Basic]: Protecting devices',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Identify basic digital security risks for devices and understand basic security practices.</p><p><strong>Practical Examples:</strong></p><ul><li>Identify common risks such as malware and understand the importance of regular operating system updates.</li><li>Use strong passwords for devices and avoid sharing sensitive information on unsecured public Wi-Fi networks.</li><li>Recognise potential threats associated with phishing emails and browsing on unsecured websites, and use basic antivirus software.</li><li>Use a password manager to store your login details securely and encourage the use of two-factor authentication.</li><li>Comply with the institution’s security policy.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_B_1_INTERMEDIATE',
                                        'shortname' => 'B.1 [Level Intermediate]: Protecting devices',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Actively manage the security of your digital devices and apply advanced protection and prevention practices.</p><p><strong>Practical Examples:</strong></p><ul><li>Use advanced security tools (such as firewalls, antivirus software and VPNs) to secure your devices and connections.</li><li>Use user accounts with restricted privileges on your devices to limit vulnerabilities in the event of unauthorised access.</li><li>Regularly back up your data to encrypted cloud storage services to protect yourself against data loss.</li><li>Notify the IT department when a security incident is identified.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_B_1_ADVANCED',
                                        'shortname' => 'B.1 [Level Advanced]: Protecting devices',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Anticipate, assess and implement complex security strategies for digital devices, and contribute to awareness-raising and management.</p><p><strong>Practical Examples:</strong></p><ul><li>Assess security risks specific to the institutional environment and implement advanced security protocols for devices, including regular vulnerability scans.</li><li>Participate in training sessions and workshops to raise awareness among colleagues and students about digital risks, and contribute to the development of security policies at the institutional level.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_B_2',
                                'shortname' => 'B.2. Protecting personal data',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_B_2_BASIC',
                                        'shortname' => 'B.2 [Level Basic]: Protecting personal data',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Understand the risks associated with data disclosure and adopt basic practices to protect your data.</p><p><strong>Practical Examples:</strong></p><ul><li>Use unique and complex passwords for online accounts, avoiding the reuse of the same passwords.</li><li>Adjust privacy settings on social media and understand the risks associated with disclosing personal information online.</li><li>Use privacy tools when browsing the web, such as tracker blockers or privacy browser extensions.</li><li>Understand the fundamental principles of the GDPR (General Data Protection Regulation).</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_B_2_INTERMEDIATE',
                                        'shortname' => 'B.2 [Level Intermediate]: Protecting personal data',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Actively manage data security, implement advanced measures and assess online risks.</p><p><strong>Practical Examples:</strong></p><ul><li>Use encryption tools to secure sensitive communications, for example by using VPNs when browsing on unsecured networks.</li><li>Check the access permissions of online applications and services to limit excessive data collection.</li><li>Apply the fundamental principles of the GDPR and consult the DPO (Data Protection Officer) on this subject if necessary.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_B_2_ADVANCED',
                                        'shortname' => 'B.2 [Level Advanced]: Protecting personal data',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Develop advanced strategies to secure data, raise awareness and implement institutional security protocols.</p><p><strong>Practical Examples:</strong></p><ul><li>Design and deliver data protection training for students and colleagues, covering best practices in data protection.</li><li>Contribute to the development of institutional data protection policies, helping to create security protocols and best practices.</li><li>Develop data management protocols specific to your area of expertise, ensuring the confidentiality of sensitive data within your department or service.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_B_3',
                                'shortname' => 'B.3. Protecting health, well-being and the environment',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_B_3_BASIC',
                                        'shortname' => 'B.3 [Level Basic]: Protecting health, well-being and the environment',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Recognise the risks to health and well-being associated with excessive use of digital technologies.</p><p><strong>Practical Examples:</strong></p><ul><li>Identify signs of digital overload, such as stress linked to managing multiple online learning platforms.</li><li>Identify the consequences of being constantly connected on one’s well-being, such as eye strain and dependence on digital tools.</li><li>Recognise the impact of digital overload on one’s professional environment and look for ways to reduce this pressure.</li><li>Regularly delete unnecessary emails from your inbox to reduce the energy consumption of email servers.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_B_3_INTERMEDIATE',
                                        'shortname' => 'B.3 [Level Intermediate]: Protecting health, well-being and the environment',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Adopt practices aimed at promoting mental health and preventing potential risks associated with the use of digital environments.</p><p><strong>Practical Examples:</strong></p><ul><li>Disconnect from digital devices at appropriate times to safeguard one’s health and well-being, with the aim of fostering a work-life balance.</li><li>Promote institutional policies that encourage disconnection.</li><li>Initiate discussions on managing digital stress during departmental or staff meetings, thereby promoting a more thoughtful and mindful use of digital tools.</li><li>Encourage the implementation of energy-saving policies in classrooms, such as switching off devices when they are not in use during breaks.</li><li>Regularly assess (and limit) the amount of data stored online, particularly on cloud services, by deleting unused files or transferring non-essential data to storage media with a low environmental impact.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_B_3_ADVANCED',
                                        'shortname' => 'B.3 [Level Advanced]: Protecting health, well-being and the environment',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Develop initiatives to raise awareness of the impact of technology on mental health and the environment, whilst promoting responsible and sustainable behaviour.</p><p><strong>Practical Examples:</strong></p><ul><li>Invest in institutional programmes on digital well-being to raise awareness among teaching staff of the risks and solutions.</li><li>Offer sustainable alternatives to digital practices, such as screen-free reflection sessions during teaching meetings to encourage more in-depth and engaged discussions.</li><li>Raise students’ awareness of the environmental impact of printing their work by recommending sustainable practices. Encourage printing using ink-efficient fonts, recycled paper, double-sided printing, omitting cover pages and using smaller paper sizes to minimise the environmental footprint.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D1',
                        'shortname' => '1. Professional Engagement',
                        'description' => '<p>Using digital technologies for institutional communication, professional collaboration, reflective practice, and continuing professional development.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_1_1',
                                'shortname' => '1.1. Using digital technology in organisational communication',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_1_1_BASIC',
                                        'shortname' => '1.1 [Level Basic]: Using digital technology in organisational communication',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use the institution’s digital communication platforms (email, LMS, intranet) to access basic information and share it with colleagues and management.</p><p><strong>Practical Examples:</strong></p><ul><li>Use the official email system (Outlook, Gmail) for institutional communication.</li><li>Access the institution’s LMS to receive information, submit assignments and participate in online learning.</li><li>Participate in online meetings via Zoom, Teams, Google Meet.</li><li>Update personal details in the staff management system.</li><li>Submit leave requests and administrative documents via the institutional portal.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_1_INTERMEDIATE',
                                        'shortname' => '1.1 [Level Intermediate]: Using digital technology in organisational communication',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Actively participate in digital communication channels, select platforms suited to different objectives, and effectively coordinate work with multiple stakeholders.</p><p><strong>Practical Examples:</strong></p><ul><li>Create and manage shared workspaces (Google Drive, OneDrive, SharePoint) for collaboration within the department.</li><li>Use project management tools (Trello, Asana, Microsoft Planner, Notion) to organise team tasks.</li><li>Facilitate the use of advanced meeting features (chat rooms, polls, recording) during online sessions.</li><li>Create digital content for departmental/faculty websites.</li><li>Manage calendars and events via Google Calendar or Outlook</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_1_ADVANCED',
                                        'shortname' => '1.1 [Level Advanced]: Using digital technology in organisational communication',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Lead and optimise the institution’s digital communication strategies, establish professional communication standards and mentor colleagues in the implementation of effective digital engagement practices.</p><p><strong>Practical Examples:</strong></p><ul><li>Design a comprehensive digital communication strategy in line with Decision 131/QĐ-TTg on digital transformation.</li><li>Propose innovative solutions (AI chatbots, automated notification systems, mobile apps).</li><li>Develop policies in line with the Cybersecurity Law 24/2018/QH14.</li><li>Evaluate the effectiveness of communication using Google Analytics and Teams analytics.</li><li>Represent the institution at forums on digital transformation organised by the AUN (ASEAN University Network).</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_1_2',
                                'shortname' => '1.2. Using digital technology in professional collaboration',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_1_2_BASIC',
                                        'shortname' => '1.2 [Level Basic]: Using digital technology in professional collaboration',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use basic digital tools (shared documents, group discussions) to collaborate with colleagues on simple tasks and projects.</p><p><strong>Practical Examples:</strong></p><ul><li>Share teaching materials via email and cloud storage.</li><li>Participate in your department’s WhatsApp/Zalo/Teams groups.</li><li>Use the commenting features in Google Docs/Word Online to give feedback.</li><li>Take part in work meetings involving basic online interactions.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_2_INTERMEDIATE',
                                        'shortname' => '1.2 [Level Intermediate]: Using digital technology in professional collaboration',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Make use of collaborative platforms (Microsoft Teams, Google Workspace, Zoom) to co-create content, manage joint projects and participate in cross-departmental initiatives.</p><p><strong>Practical Examples:</strong></p><ul><li>Co-develop course content on Google Workspace/Microsoft 365.</li><li>Create learning communities on Facebook groups, LinkedIn and ResearchGate.</li><li>Collaborate on research using Zotero/Mendeley for reference management.</li><li>Collect research data via Google Forms/SurveyMonkey/Qualtrics.</li><li>Organise inter-institutional webinars using Zoom Webinar or Teams Live Events.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_2_ADVANCED',
                                        'shortname' => '1.2 [Level Advanced]: Using digital technology in professional collaboration',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Design and facilitate collaborative digital ecosystems, drive institutional innovation through inter-university partnerships, and integrate emerging technologies into collaborative workflows.</p><p><strong>Practical Examples:</strong></p><ul><li>Set up international research projects on Slack/GitHub/OSF (Open Science Framework).</li><li>Develop digital collaboration frameworks for the institution.</li><li>Publish research on digital collaboration practices.</li><li>Create and coordinate MOOCs on edX/Coursera/FutureLearn.</li><li>Represent the institution within the AUN, SEAMEO (South-East Asian Ministers of Education Organisation) and the working groups of the Ministry of Education and Training.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_1_3',
                                'shortname' => '1.3. Applying reflective practice in the use of digital technology',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_1_3_BASIC',
                                        'shortname' => '1.3 [Level Basic]: Applying reflective practice in the use of digital technology',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Document teaching and work experiences using digital tools, and seek feedback via the institution’s digital platforms.</p><p><strong>Practical Examples:</strong></p><ul><li>Keep a digital teaching journal (Word, Google Docs, Evernote, OneNote).</li><li>Take photos/videos of classroom activities for analysis.</li><li>Complete online self-assessment questionnaires.</li><li>Review student feedback from the institution’s assessment system.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_3_INTERMEDIATE',
                                        'shortname' => '1.3 [Level Intermediate]: Applying reflective practice in the use of digital technology',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Systematically analyse teaching and working practices using digital portfolios, student feedback systems and data analysis to improve professional effectiveness.</p><p><strong>Practical Examples:</strong></p><ul><li>Maintain a comprehensive digital teaching portfolio (Mahara, WordPress, Google Sites).</li><li>Record and analyse teaching sessions using Zoom/Panopto/Kaltura.</li><li>Use learning analytics from LMS platforms (Moodle, Teams, Google Classroom).</li><li>Create reflective blogs/vlogs on Medium, WordPress or YouTube.</li><li>Use digital mind-mapping tools (MindMeister, Coggle, Miro) to analyse teaching practices.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_3_ADVANCED',
                                        'shortname' => '1.3 [Level Advanced]: Applying reflective practice in the use of digital technology',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Conduct a critical, evidence-based evaluation of digital teaching practices, publish reflections on academic platforms and contribute to institutional quality assurance frameworks.</p><p><strong>Practical Examples:</strong></p><ul><li>Design frameworks for reflective practice for the department.</li><li>Publish research in peer-reviewed journals (Reflective Practice, Teaching in Higher Education).</li><li>Conduct action research using NVivo/MAXQDA/Atlas.ti.</li><li>Use AI tools (ChatGPT, Claude) for in-depth reflective analysis.</li><li>Integrate VR/AR into reflective practice initiatives.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_1_4',
                                'shortname' => '1.4. Using digital technology in continuing professional development (CPD)',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_1_4_BASIC',
                                        'shortname' => '1.4 [Level Basic]: Using digital technology in continuing professional development (CPD)',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Access and complete basic online training courses offered by the institution or platforms approved by the Ministry of Education and Training (MOET) to update one’s digital competences.</p><p><strong>Practical Examples:</strong></p><ul><li>Take the online training courses offered by the institution or the Department for Education.</li><li>Take basic courses on national platforms and MOOCs [Massive Open Online Courses] (Coursera, edX, FutureLearn).</li><li>Watch educational videos on YouTube and LinkedIn Learning.</li><li>Join professional groups on Facebook, Zalo and LinkedIn.</li><li>Access open educational resources (OER Commons, MERLOT, MIT, OpenCourseWare).</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_4_INTERMEDIATE',
                                        'shortname' => '1.4 [Level Intermediate]: Using digital technology in continuing professional development (CPD)',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Proactively identify learning needs, select relevant MOOCs/webinars/professional networks and apply new digital competences to improve teaching and administrative work.</p><p><strong>Practical Examples:</strong></p><ul><li>Develop a personal professional development plan using Excel, Google Sheets, Notion or Trello.</li><li>Take advanced MOOCs on educational technology, pedagogy and research.</li><li>Obtain micro-certifications and digital badges (Quality Matters, ACUE).</li><li>Use RSS feeds (Feedly) and academic databases (Google Scholar, ERIC, Scopus).</li><li>Participate in and speak at national and international virtual conferences.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_1_4_ADVANCED',
                                        'shortname' => '1.4 [Level Advanced]: Using digital technology in continuing professional development (CPD)',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Design and deliver digital continuing professional development (CPD) programmes for colleagues, engage with international professional learning communities, and lead institutional capacity-building initiatives in the field of emerging technologies.</p><p><strong>Practical Examples:</strong></p><ul><li>Design online professional development programmes at institutional, national and international levels.</li><li>Act as a consultant or coach in digital learning.</li><li>Develop continuing professional development (CPD) frameworks aligned with Ministry of Education standards and international best practices.</li><li>Create and coordinate MOOCs for teacher training.</li><li>Represent the institution on national committees on digital transformation.</li><li>Establish partnerships with the British Council, Fulbright, the DAAD and Erasmus+.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D2',
                        'shortname' => '2. Digital Resources',
                        'description' => '<p>Selecting, creating, modifying, managing, protecting, and sharing digital educational resources.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_2_1',
                                'shortname' => '2.1. Selecting resources',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_2_1_BASIC',
                                        'shortname' => '2.1 [Level Basic]: Selecting resources',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use basic digital technologies to search for digital resources.</p><p><strong>Practical Examples:</strong></p><ul><li>Use an online search engine to find digital resources related to one’s teaching subject.</li><li>Choose appropriate keywords for online searches and browse websites to find basic information.</li><li>Be accustomed to accessing digital resources available on the agency/unit’s website.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_2_1_INTERMEDIATE',
                                        'shortname' => '2.1 [Level Intermediate]: Selecting resources',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Identify and evaluate appropriate digital resources using criteria (basic and complex).</p><p><strong>Practical Examples:</strong></p><ul><li>Identify digital resources related to their teaching materials based on learning outcome criteria.</li><li>Use advanced search engines (with or without AI) by selecting keywords and logical operators to refine searches and find high-quality resources.</li><li>Assess the credibility of sources by examining authors, institutional affiliations and publication dates.</li><li>Access the organisation’s digital resources effectively (such as the intranet, departmental intranet, library, etc.)</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_2_1_ADVANCED',
                                        'shortname' => '2.1 [Level Advanced]: Selecting resources',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Comprehensively identify, evaluate and promote appropriate digital resources, taking into account all relevant aspects.</p><p><strong>Practical Examples:</strong></p><ul><li>Identify and evaluate digital resources, taking into account criteria such as pedagogical suitability and students’ specific needs.</li><li>Actively promote these resources within one’s department, service, faculty and institution, thereby contributing to the enrichment of the institution’s digital teaching practices.</li><li>Keep abreast of the latest developments in one’s field of teaching by using web feeds/RSS and bibliographic tools, and ensure the responsible use of digital resources in one’s teaching activities.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_2_2',
                                'shortname' => '2.2. Create and edit resources',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_2_2_BASIC',
                                        'shortname' => '2.2 [Level Basic]: Create and edit resources',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Create and edit digital resources using basic tools and strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Create simple digital teaching materials using basic tools such as PowerPoint or Word.</li><li>Incorporate handwritten content into digital materials and create simple diagrams and tables.</li><li>Use the available digital resources to enhance teaching materials.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_2_2_INTERMEDIATE',
                                        'shortname' => '2.2 [Level Intermediate]: Create and edit resources',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Create and edit digital resources using certain advanced features and adapt them to a specific learning context.</p><p><strong>Practical Examples:</strong></p><ul><li>Create digital course materials using more advanced tools (such as Genially, H5P, Moodle Book, LaTeX, etc.), which allow resources to be adapted to specific learning contexts.</li><li>Produce materials accessible to students with special needs.</li><li>Extract diagrams or data from external sources (such as reference articles, library books, etc.) and integrate them appropriately into course materials.</li><li>Apply and comply with Creative Commons licences for the resources they create, modify or adapt.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_2_2_ADVANCED',
                                        'shortname' => '2.2 [Level Advanced]: Create and edit resources',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Create, co-create and modify complex, interactive digital resources tailored to the learning context, using a range of advanced strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Create and edit complex, interactive digital resources designed specifically to suit the learning context.</li><li>Apply advanced instructional design techniques to enhance their impact on learners.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_2_3',
                                'shortname' => '2.3. Manage, protect and share resources',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_2_3_BASIC',
                                        'shortname' => '2.3 [Level Basic]: Manage, protect and share resources',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Store and manage digital resources using basic strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Organise digital files in a structured manner on your computer.</li><li>Regularly back up important work and digital resources to an external device (such as an external hard drive, USB stick, etc.) to prevent data loss.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_2_3_INTERMEDIATE',
                                        'shortname' => '2.3 [Level Intermediate]: Manage, protect and share resources',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Share digital resources professionally and protect them effectively.</p><p><strong>Practical Examples:</strong></p><ul><li>Organise and share digital resources (for example on Moodle) to enable students to access content easily.</li><li>Share documents with colleagues using a cloud storage service (such as OneDrive, Google Drive).</li><li>Use a version control system (such as Git, OneDrive, etc.)</li><li>Take protective measures to ensure the security of sensitive data and process it in accordance with GDPR regulations.</li><li>Comply with Creative Commons licences for the resources they share.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_2_3_ADVANCED',
                                        'shortname' => '2.3 [Level Advanced]: Manage, protect and share resources',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Publish digital resources professionally.</p><p><strong>Practical Examples:</strong></p><ul><li>Distribute their work whilst protecting it with appropriate licences.</li><li>Understand the institution’s brand guidelines and consistently apply the required communication style when publishing digital resources on behalf of the institution, thereby ensuring professional and consistent communication.</li><li>Create shared documents and folders (such as SharePoint, GitHub, Overleaf, etc.) between teachers to share resources.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D3',
                        'shortname' => '3. Teaching and Learning',
                        'description' => '<p>Designing and implementing digital teaching, supporting learners, fostering collaborative and self-directed learning, and applying emerging technologies.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_3_1',
                                'shortname' => '3.1. Designing and implementing digital teaching',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_3_1_BASIC',
                                        'shortname' => '3.1 [Level Basic]: Designing and implementing digital teaching',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use basic technologies for teaching.</p><p><strong>Practical Examples:</strong></p><ul><li>Use presentation software to prepare lecture slides, use Google Classroom to manage and communicate with students, use Google Meet for online teaching…</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_1_INTERMEDIATE',
                                        'shortname' => '3.1 [Level Intermediate]: Designing and implementing digital teaching',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Use available educational technologies to enhance teaching strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Take into account the context and educational objectives when integrating digital tools.</li><li>Create interactive presentations (such as Prezi, Genially, etc.) to make your course materials more engaging.</li><li>Incorporate simple online activities (such as quizzes) into your lessons to assess students’ understanding.</li><li>Offer students a combination of digital resources and traditional course materials to diversify teaching approaches.</li><li>When teaching remotely, use a tool that allows you to annotate documents in real time.</li><li>Use an online platform (such as Moodle or Teams) to organise discussions and forums to encourage student participation.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_1_ADVANCED',
                                        'shortname' => '3.1 [Level Advanced]: Designing and implementing digital teaching',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Use and adapt teaching technologies to improve and innovate your teaching strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Design fully online learning pathways with interactive elements such as simulations, serious games or educational videos.</li><li>Adapt digital teaching practices to the diversity of student profiles, allowing them to choose from a range of tools to carry out their projects.</li><li>Use artificial intelligence tools to personalise the learning experience according to students’ individual needs.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_3_2',
                                'shortname' => '3.2. Providing support and guidance to students',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_3_2_BASIC',
                                        'shortname' => '3.2 [Level Basic]: Providing support and guidance to students',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use basic digital tools to interact with students.</p><p><strong>Practical Examples:</strong></p><ul><li>Include questions in your presentation materials to allow students to answer and provide immediate feedback on their understanding.</li><li>Respond to questions asked by students via digital channels (e.g. by email).</li><li>Collect questions from students (e.g. via email, surveys, etc.) and use them during your lesson, thereby encouraging student interaction and participation.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_2_INTERMEDIATE',
                                        'shortname' => '3.2 [Level Intermediate]: Providing support and guidance to students',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Use digital technologies to improve interaction, monitoring and support for students.</p><p><strong>Practical Examples:</strong></p><ul><li>Use polling systems (such as Wooclap, Kahoot, Plickers) to encourage classroom interaction.</li><li>Use a meeting scheduling tool (such as Moodle Calendar, Microsoft Booking, etc.) to offer more personalised support to students who request it.</li><li>Diversify interaction methods by alternating between synchronous tools (such as Wooclap, Microsoft Whiteboard, etc.) and asynchronous tools (such as forums, Articulate, H5P, etc.)</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_2_ADVANCED',
                                        'shortname' => '3.2 [Level Advanced]: Providing support and guidance to students',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Use digital technologies strategically and purposefully to guide, advise and innovate in student support.</p><p><strong>Practical Examples:</strong></p><ul><li>Use chatbots to answer frequently asked questions from students outside of teaching hours.</li><li>Use data from a Learning Record Store (LRS) to track and analyse students’ progress, and then provide personalised guidance tailored to their specific needs.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_3_3',
                                'shortname' => '3.3. Promoting collaborative learning',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_3_3_BASIC',
                                        'shortname' => '3.3 [Level Basic]: Promoting collaborative learning',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Encourage students to use technology in their collaborative learning activities.</p><p><strong>Practical Examples:</strong></p><ul><li>Encourage students to use common digital collaboration tools for their group work to facilitate communication and coordination.</li><li>Provide basic collaborative tools (such as Google Docs, Microsoft Word Online, Overleaf, etc.) to enable students to collaborate or work together on online projects.</li><li>Encourage students to share their personal experiences of collaboration on an online discussion forum (such as Forum, Padlet, etc.)</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_3_INTERMEDIATE',
                                        'shortname' => '3.3 [Level Intermediate]: Promoting collaborative learning',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Use digital technologies and environments to design and support collaborative learning activities.</p><p><strong>Practical Examples:</strong></p><ul><li>Use institutional tools to create online student groups (such as Moodle groups, Teams) that enable more effective management of group projects.</li><li>Encourage the use of specific tools for group projects (such as Microsoft Teams, Trello, Padlet, Digipad, etc.) to ensure better coordination and communication.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_3_ADVANCED',
                                        'shortname' => '3.3 [Level Advanced]: Promoting collaborative learning',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Use digital environments for collaborative knowledge production with students, peer assessment and to innovate in student collaboration.</p><p><strong>Practical Examples:</strong></p><ul><li>Use advanced digital environments that enable students to collaborate in more sophisticated ways, such as virtual reality platforms for immersive collaborative projects.</li><li>Encourage collaborative knowledge production by implementing online peer assessment (such as the ‘Workshop’ activity on Moodle), where students grade and comment on their peers’ work.</li><li>Integrate participatory design methods (such as GitHub) into group projects, thereby enabling students to co-create innovative solutions.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_3_4',
                                'shortname' => '3.4. Encouraging self-directed learning',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_3_4_BASIC',
                                        'shortname' => '3.4 [Level Basic]: Encouraging self-directed learning',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Encourage students to use digital technologies to support self-directed learning.</p><p><strong>Practical Examples:</strong></p><ul><li>Encourage students to use basic digital tools to plan their own learning, such as online calendar apps to track assignment deadlines.</li><li>Provide multimedia content to help students develop their self-directed learning skills.</li><li>Encourage students to share their ideas and reflections on an online forum dedicated to managing their learning.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_4_INTERMEDIATE',
                                        'shortname' => '3.4 [Level Intermediate]: Encouraging self-directed learning',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Use digital technologies in the design of activities aimed at supporting self-regulated learning.</p><p><strong>Practical Examples:</strong></p><ul><li>Integrate online dashboards where students can track their own progress (such as tracking achievements in Moodle, LRS).</li><li>Provide information on the use of institutional tools specifically designed to support self-regulated learning, such as mobile apps that allow students to plan their timetables and receive reminders for their tasks (such as Microsoft ToDo).</li><li>Offer online self-learning modules accompanied by self-assessment quizzes, enabling students to assess themselves and gauge their understanding.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_4_ADVANCED',
                                        'shortname' => '3.4 [Level Advanced]: Encouraging self-directed learning',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Develop critical thinking about the digital strategies used in order to devise new digital strategies and/or pedagogical approaches for self-regulated learning.</p><p><strong>Practical Examples:</strong></p><ul><li>Use cutting-edge technologies, such as data analytics, to personalise learning.</li><li>Encourage students to create their own digital tools to plan, monitor and reflect on their learning, thereby fostering creativity and autonomy.</li><li>Implement self-assessment activities enabling students to receive detailed feedback on their performance.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_3_5',
                                'shortname' => '3.5. Apply emerging technologies (AI, VR, etc.)',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_3_5_BASIC',
                                        'shortname' => '3.5 [Level Basic]: Apply emerging technologies (AI, VR, etc.)',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use emerging technologies at a basic level to support teaching activities, primarily through pre-defined tools or guided workflows.</p><p><strong>Practical Examples:</strong></p><ul><li>Use ChatGPT/Bard/Claude to generate multiple-choice questions based on existing templates.</li><li>Use AI-powered slide creation tools (e.g. Gamma, Canva AI) with default templates.</li><li>Use available 360° VR videos to illustrate lessons (e.g., a 360° factory tour or a virtual laboratory).</li><li>Run PhET simulations based on predefined exercises or guided instructions.</li><li>Use AI to suggest a lesson plan without extensive customisation.</li><li>Use simple AR applications (such as AR in Google Search) to display 3D objects for demonstration purposes.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_5_INTERMEDIATE',
                                        'shortname' => '3.5 [Level Intermediate]: Apply emerging technologies (AI, VR, etc.)',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Independently apply emerging technologies to enhance learning activities, select appropriate tools for different contexts, and adapt them to students’ needs.</p><p><strong>Practical Examples:</strong></p><ul><li>Independently select AI tools suited to different teaching objectives, such as using ChatGPT for writing support, Copilot for programming tasks and Pi for critical thinking activities.</li><li>Customise simulations according to students’ skill levels (for example, by adjusting parameters or difficulty levels).</li><li>Design learning activities using virtual reality labs, such as virtual chemistry experiments or safety training simulations.</li><li>Create AR revision cards or AR markers to help students explore course content interactively.</li><li>Use AI-assisted writing assessment tools, aligned with customised marking schemes.</li><li>Create engaging learning activities using platforms such as Genially or H5P, with extensive interactive elements.</li><li>Integrate AI into classroom activities, such as AI-assisted brainstorming, AI-moderated debates or AI tutoring.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_3_5_ADVANCED',
                                        'shortname' => '3.5 [Level Advanced]: Apply emerging technologies (AI, VR, etc.)',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Design and innovate learning experiences using emerging technologies; integrate AI, VR and AR to solve complex pedagogical problems and guide colleagues in advanced applications.</p><p><strong>Practical Examples:</strong></p><ul><li>Design a fully immersive learning experience using VR/AR/AI (e.g. a surgical simulation or a virtual laboratory environment).</li><li>Develop a personalised, AI-powered learning system in which an AI tutor tracks students’ progress and recommends tailored learning pathways.</li><li>Create a reusable set of XR courses (combining AR, VR and 3D models) for the entire teaching staff.</li><li>Integrate several emerging technologies, such as: AI → analysis of learning behaviours; VR → skills practice; Simulations → skills assessment.</li><li>Train and support colleagues in the application of AI, VR and AR to instructional design and digital pedagogy.</li><li>Develop a MOOC incorporating emerging technologies (interactive videos, AI assistants, simulations).</li><li>Experiment with cutting-edge technologies (e.g. digital twins, classrooms in the metaverse) to address complex pedagogical challenges.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D4',
                        'shortname' => '4. Assessment',
                        'description' => '<p>Using digital technologies in assessment strategies, learning evidence analytics, and timely feedback for continuous improvement.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_4_1',
                                'shortname' => '4.1. Using digital technology in assessment strategies',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_4_1_BASIC',
                                        'shortname' => '4.1 [Level Basic]: Using digital technology in assessment strategies',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Integrate digital technologies into traditional assessment strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Use word processing software to create assessment questionnaires.</li><li>Use presentation software to display multiple-choice questions during classroom assessments, then show the correct answers.</li><li>Use a spreadsheet to automatically calculate marks or averages from several assessments.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_4_1_INTERMEDIATE',
                                        'shortname' => '4.1 [Level Intermediate]: Using digital technology in assessment strategies',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Strategically use and adapt existing digital assessment tools and formats.</p><p><strong>Practical Examples:</strong></p><ul><li>Design continuous assessments (formative, summative, etc.) in the form of feedback techniques (such as quizzes, Wooclap, Plickers, etc.) to encourage active student participation during lessons.</li><li>Adapt traditional tests by digitising them (or using offline tests on Moodle), which speeds up marking and the analysis of results.</li><li>Set up online assessments comprising a variety of questions on the Moodle platform, allowing learners to submit their answers electronically and the teacher to mark them online.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_4_1_ADVANCED',
                                        'shortname' => '4.1 [Level Advanced]: Using digital technology in assessment strategies',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Select, create and adapt digital assessment formats in a comprehensive, critical and innovative manner.</p><p><strong>Practical Examples:</strong></p><ul><li>Explore artificial intelligence tools, such as text analysis, to assess the quality of students’ work, providing instant feedback and marks based on a marking scheme.</li><li>Create complex digital assessments, such as interactive multimedia projects, which require digital creation skills from students, thereby fostering their creativity and a deeper understanding of the subject matter.</li><li>Develop adaptive assessments, where subsequent questions depend on students’ previous answers, thereby offering a personalised learning experience.</li><li>Design interactive digital simulations to assess understanding of complex concepts, thereby providing students with an immersive learning environment.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_4_2',
                                'shortname' => '4.2. Using digital technology in learning analysis',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_4_2_BASIC',
                                        'shortname' => '4.2 [Level Basic]: Using digital technology in learning analysis',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Assess basic data on student activity and performance.</p><p><strong>Practical Examples:</strong></p><ul><li>Process, analyse and understand information derived from students’ activity logs and online comments.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_4_2_INTERMEDIATE',
                                        'shortname' => '4.2 [Level Intermediate]: Using digital technology in learning analysis',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Strategically use digital tools to generate data and utilise it to improve teaching strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Implement online formative assessments to evaluate students’ understanding throughout the course, then analyse the results to identify gaps and adapt lessons.</li><li>Regularly review student activity on the e-learning platform, identifying the most and least viewed sections of the course in order to adjust content and communication.</li><li>Use learning analytics tools to analyse student behaviour, including time spent on activities, formative test results and pages viewed, in order to adjust course planning.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_4_2_ADVANCED',
                                        'shortname' => '4.2 [Level Advanced]: Using digital technology in learning analysis',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Use digital data to reflect on and develop learning methods and teaching strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Analyse learning analytics data to design personalised interventions for students experiencing difficulties.</li><li>Explore advanced data analysis techniques, such as learning sequence analysis, to identify specific learning pathways and adapt activities accordingly.</li><li>Monitor student retention data over time, identify factors influencing student engagement and retention, and propose major changes to instructional design to improve learning effectiveness.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_4_3',
                                'shortname' => '4.3. Using digital technology in feedback and continuous improvement',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_4_3_BASIC',
                                        'shortname' => '4.3 [Level Basic]: Using digital technology in feedback and continuous improvement',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use digital technologies to provide feedback to students.</p><p><strong>Practical Examples:</strong></p><ul><li>Send personalised feedback to students to comment on their work and answer their questions.</li><li>Use online survey tools to gather students’ feedback on learning activities and adapt them accordingly.</li><li>Offer virtual drop-in sessions via video conferencing platforms (such as Teams, etc.) to discuss students’ progress individually and provide them with real-time feedback.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_4_3_INTERMEDIATE',
                                        'shortname' => '4.3 [Level Intermediate]: Using digital technology in feedback and continuous improvement',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Use digital data to improve the effectiveness of feedback and support.</p><p><strong>Practical Examples:</strong></p><ul><li>Use a student progress tracking tool to monitor their progress in learning activities and tailor feedback based on this data.</li><li>Integrate realistic automatic deadlines into the e-learning platform (such as Moodle), based on data regarding students’ task completion.</li><li>Use digital annotations to provide feedback directly on students’ work, highlighting specific sections or embedding comments directly into the document.</li><li>Use dedicated survey applications, from distribution to surveys and activity reports.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_4_3_ADVANCED',
                                        'shortname' => '4.3 [Level Advanced]: Using digital technology in feedback and continuous improvement',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Use digital technologies to personalise feedback, evaluate and improve teaching strategies.</p><p><strong>Practical Examples:</strong></p><ul><li>Use advanced analysis of student performance data (for example, via an LRS) to personalise feedback by identifying areas where each student can improve.</li><li>Use various formats of digital feedback (audio, video, etc.) to provide more detailed and interactive feedback to students.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D5',
                        'shortname' => '5. Taking Students’ Needs into Account',
                        'description' => '<p>Ensuring digital accessibility and inclusion, promoting differentiation and personalisation, and encouraging active student engagement.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_5_1',
                                'shortname' => '5.1. Ensuring digital accessibility and inclusion',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_5_1_BASIC',
                                        'shortname' => '5.1 [Level Basic]: Ensuring digital accessibility and inclusion',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use basic digital tools to ensure that teaching materials are accessible to all students, in accordance with the institution’s accessibility guidelines.</p><p><strong>Practical Examples:</strong></p><ul><li>Upload lecture slides in a readable format (font size, contrast) to the learning management system (LMS) so that all students can read them easily.</li><li>Provide downloadable PDF versions of essential materials for students with a poor internet connection.</li><li>Add subtitles or transcripts to short educational videos where possible.</li><li>Use a microphone and check the sound quality in online or blended learning sessions so that everyone can hear clearly.</li><li>Apply institutional templates for accessible documents (headings, alternative text, simple layout).</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_5_1_INTERMEDIATE',
                                        'shortname' => '5.1 [Level Intermediate]: Ensuring digital accessibility and inclusion',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Proactively design and adapt digital learning activities, taking into account the diverse needs of learners (disabilities, language barriers, learning preferences) using appropriate tools.</p><p><strong>Practical Examples:</strong></p><ul><li>Adapt course materials into multiple formats (slides, text summaries, audio) to meet students’ varied needs.</li><li>Use the LMS’s accessibility tools (e.g. validators, alternative text fields) to improve the accessibility of resources.</li><li>Identify students who need support (visual/hearing impairments, specific conditions) and adapt digital materials accordingly.</li><li>Organise online consultation sessions for students who are unable to attend face-to-face classes.</li><li>Collaborate with the university’s support centre to ensure the accessibility of digital exams and quizzes.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_5_1_ADVANCED',
                                        'shortname' => '5.1 [Level Advanced]: Ensuring digital accessibility and inclusion',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Critically evaluate the accessibility of digital resources, systematically apply universal design principles, and guide colleagues in creating inclusive learning environments.</p><p><strong>Practical Examples:</strong></p><ul><li>Apply the principles of Universal Design for Learning (UDL) when planning courses and digital materials.</li><li>Lead a faculty-led initiative to review and improve the accessibility of all core online courses.</li><li>Support colleagues in creating accessible digital content (e.g. subtitles, screen reader-compatible documents).</li><li>Work with IT services to integrate accessibility tools (screen readers, text-to-speech, contrast modes) into institutional platforms.</li><li>Contribute to institutional policies or guidelines on inclusive and accessible digital learning.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_5_2',
                                'shortname' => '5.2. Promoting differentiation and personalisation through digital technology',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_5_2_BASIC',
                                        'shortname' => '5.2 [Level Basic]: Promoting differentiation and personalisation through digital technology',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use digital tools to provide alternative learning materials (videos, documents, exercises) enabling students to access content in different ways.</p><p><strong>Practical Examples:</strong></p><ul><li>Provide optional supplementary resources (videos, readings, practical exercises) to students who need to revise or deepen their knowledge.</li><li>Offer practice quizzes of varying difficulty levels on the LMS platform for the same topic.</li><li>Allow students to choose between submitting a written report or a PowerPoint presentation for a short assignment.</li><li>Share clear weekly checklists so that students can manage their own learning tasks.</li><li>Use simple online surveys to ask students about their prior knowledge and learning preferences.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_5_2_INTERMEDIATE',
                                        'shortname' => '5.2 [Level Intermediate]: Promoting differentiation and personalisation through digital technology',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Design personalised, self-directed learning pathways using digital platforms (LMS, adaptive tools) that adapt to each student’s progress, interests and learning pace.</p><p><strong>Practical Examples:</strong></p><ul><li>Use LMS features (groups, unlock conditions, adaptive pathways) to tailor activities to students’ different levels.</li><li>Design alternative learning pathways (e.g. a ‘basic pathway’ and an ‘advanced pathway’) within a course.</li><li>Provide targeted feedback and personalised recommendations using comments, assessment grids and LMS analytics.</li><li>Use diagnostic quizzes at the start of the term to group students and adjust the pace of teaching.</li><li>Integrate adaptive learning tools or question banks that adjust the difficulty level based on students’ performance.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_5_2_ADVANCED',
                                        'shortname' => '5.2 [Level Advanced]: Promoting differentiation and personalisation through digital technology',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Leverage AI and data analytics to create highly personalised learning experiences, evaluate the effectiveness of differentiation strategies, and support institutional innovation.</p><p><strong>Practical Examples:</strong></p><ul><li>Use learning analytics dashboards to continuously design and refine personalised learning plans for students who are struggling and those achieving excellent results.</li><li>Combine AI tools (e.g. recommendation systems, chatbots) with the LMS to suggest resources and activities tailored to each student.</li><li>Coordinate with academic advisors to align digital learning data with student support and mentoring.</li><li>Lead the development of a faculty-level model for personalised learning in high-enrolment courses.</li><li>Share best practices and research findings on differentiated and personalised learning at institutional or national conferences.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_5_3',
                                'shortname' => '5.3. Encouraging active student engagement',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_5_3_BASIC',
                                        'shortname' => '5.3 [Level Basic]: Encouraging active student engagement',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use interactive digital tools (polls, quizzes, discussion forums) to encourage student participation and engagement in online and hybrid courses.</p><p><strong>Practical Examples:</strong></p><ul><li>Use live polls (Mentimeter, Kahoot, Zoom polls) at the start or end of the lesson to check understanding.</li><li>Create simple online quizzes to maintain student engagement during or after classes.</li><li>Set up discussion forums on the LMS and ask students to post at least one idea or question each week.</li><li>Use the chat and reaction features in Zoom/Teams to encourage participation during online sessions.</li><li>Ask students to submit brief reflections via the LMS after important lessons.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_5_3_INTERMEDIATE',
                                        'shortname' => '5.3 [Level Intermediate]: Encouraging active student engagement',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Design collaborative digital activities (group projects, peer assessment, virtual teamwork) that promote active learning and student autonomy in various learning contexts.</p><p><strong>Practical Examples:</strong></p><ul><li>Design group projects where students collaborate online using shared documents, project boards and video meetings.</li><li>Set up peer assessment activities using LMS tools (peer assessment, peer grading).</li><li>Use problem-based or case-based learning scenarios, supported by digital resources.</li><li>Organise online debates or role-play sessions on course topics using video conferencing tools.</li><li>Combine synchronous sessions with asynchronous tasks (forums, wikis, shared notes) to maintain continuous engagement.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_5_3_ADVANCED',
                                        'shortname' => '5.3 [Level Advanced]: Encouraging active student engagement',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Strategically integrate emerging technologies (VR/AR, gamification, social media) to maximise engagement, critically evaluate learning analytics and drive pedagogical innovation.</p><p><strong>Practical Examples:</strong></p><ul><li>Integrate gamification elements (badges, points, leaderboards) into LMS activities to boost motivation.</li><li>Use VR/AR or simulation tools to create immersive learning experiences for complex concepts.</li><li>Analyse engagement data (logins, activity completion, forum participation) to redesign activities to encourage deeper engagement.</li><li>Co-create learning activities with students (student-generated content, student-led seminars) using digital platforms.</li><li>Lead innovation projects that test new interactive technologies (social annotation tools, interactive videos, educational games) in large classes.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'idnumber' => 'D6',
                        'shortname' => '6. Students’ Digital Competences',
                        'description' => '<p>Facilitating learners\' digital and media literacy, communication, content creation, responsible use, and digital problem-solving competences.</p>',
                        'competencies' => [
                            [
                                'idnumber' => 'GV_6_1',
                                'shortname' => '6.1. Developing students’ digital and media literacy',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_6_1_BASIC',
                                        'shortname' => '6.1 [Level Basic]: Developing students’ digital and media literacy',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Search for information using simple keywords; select resources provided by the teacher.</p><p><strong>Practical Examples:</strong></p><ul><li>The student searches for ‘pictures of cats’ on Google and selects one to insert into a slide</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_1_INTERMEDIATE',
                                        'shortname' => '6.1 [Level Intermediate]: Developing students’ digital and media literacy',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Compare information from different sources, assess credibility and cite sources.</p><p><strong>Practical Examples:</strong></p><ul><li>The student looks up the same news story in three different newspapers to compare perspectives and check the facts.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_1_ADVANCED',
                                        'shortname' => '6.1 [Level Advanced]: Developing students’ digital and media literacy',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Critically analyse reliability and authenticity, develop research strategies and manage data.</p><p><strong>Practical Examples:</strong></p><ul><li>The student conducts research using advanced search operators (AND, OR, site:) and uses Zotero to manage their citations.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_6_2',
                                'shortname' => '6.2. Developing students’ digital communication and collaboration skills',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_6_2_BASIC',
                                        'shortname' => '6.2 [Level Basic]: Developing students’ digital communication and collaboration skills',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Use email or instant messaging to exchange information and participate passively in digital groups.</p><p><strong>Practical Examples:</strong></p><ul><li>The student sends their assignments by email to the teacher, using the correct subject line format.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_2_INTERMEDIATE',
                                        'shortname' => '6.2 [Level Intermediate]: Developing students’ digital communication and collaboration skills',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Collaborate online to co-create products, applying netiquette rules appropriately.</p><p><strong>Practical Examples:</strong></p><ul><li>Students work on a shared Google Doc, using the ‘Comments’ feature to give feedback rather than deleting their classmates’ work.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_2_ADVANCED',
                                        'shortname' => '6.2 [Level Advanced]: Developing students’ digital communication and collaboration skills',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Organise complex collaborative projects, expand communication networks on a global scale.</p><p><strong>Practical Examples:</strong></p><ul><li>The student manages a Discord/Slack channel to coordinate a class charity project, establishing links with external universities</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_6_3',
                                'shortname' => '6.3. Develop students’ ability to create digital content',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_6_3_BASIC',
                                        'shortname' => '6.3 [Level Basic]: Develop students’ ability to create digital content',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Create simple texts or presentations, edit content at a basic level (cut, paste).</p><p><strong>Practical Examples:</strong></p><ul><li>The student writes a paragraph introducing themselves in MS Word and inserts a photo of themselves.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_3_INTERMEDIATE',
                                        'shortname' => '6.3 [Level Intermediate]: Develop students’ ability to create digital content',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Edit and combine different multimedia formats, respecting copyright and licences (Creative Commons).</p><p><strong>Practical Examples:</strong></p><ul><li>The student records a video, uses CapCut to add royalty-free music, then publishes it.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_3_ADVANCED',
                                        'shortname' => '6.3 [Level Advanced]: Develop students’ ability to create digital content',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Design complex multimedia content or code, publish creative content on public platforms.</p><p><strong>Practical Examples:</strong></p><ul><li>The student develops a website dedicated to local tourism or produces a complete series of podcasts on Spotify.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_6_4',
                                'shortname' => '6.4. Develop students’ sense of responsible digital use',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_6_4_BASIC',
                                        'shortname' => '6.4 [Level Basic]: Develop students’ sense of responsible digital use',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Protect personal devices and accounts, identify common risks (scams, spam).</p><p><strong>Practical Examples:</strong></p><ul><li>The student creates a strong password (capital letters, numbers, symbols) and does not share it.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_4_INTERMEDIATE',
                                        'shortname' => '6.4 [Level Intermediate]: Develop students’ sense of responsible digital use',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Manage their digital identity and digital footprint, balance screen time (digital wellbeing).</p><p><strong>Practical Examples:</strong></p><ul><li>The student checks their privacy settings on Facebook and sets a daily limit of 30 minutes for social media.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_4_ADVANCED',
                                        'shortname' => '6.4 [Level Advanced]: Develop students’ sense of responsible digital use',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Analysing ethical issues and social impact, promoting the healthy use of technology.</p><p><strong>Practical Examples:</strong></p><ul><li>The student organises a workshop on ‘cyberbullying’ and advises younger students on how to protect themselves.</li></ul>',
                                    ],
                                ],
                            ],
                            [
                                'idnumber' => 'GV_6_5',
                                'shortname' => '6.5. Develop students’ digital problem-solving skills',
                                'description' => '',
                                'levels' => [
                                    [
                                        'idnumber' => 'GV_6_5_BASIC',
                                        'shortname' => '6.5 [Level Basic]: Develop students’ digital problem-solving skills',
                                        'description' => '<p><strong>Learning Outcome (Level Basic):</strong> Identify simple technical errors, ask for help if stuck.</p><p><strong>Practical Examples:</strong></p><ul><li>The student checks the cable connection if the mouse isn’t working before calling the teacher.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_5_INTERMEDIATE',
                                        'shortname' => '6.5 [Level Intermediate]: Develop students’ digital problem-solving skills',
                                        'description' => '<p><strong>Learning Outcome (Level Intermediate):</strong> Solve common software and hardware problems, choose the right tools for the job.</p><p><strong>Practical Examples:</strong></p><ul><li>When a video won’t open, the student searches Google for how to convert the format or downloads the VLC player.</li></ul>',
                                    ],
                                    [
                                        'idnumber' => 'GV_6_5_ADVANCED',
                                        'shortname' => '6.5 [Level Advanced]: Develop students’ digital problem-solving skills',
                                        'description' => '<p><strong>Learning Outcome (Level Advanced):</strong> Propose innovative solutions to complex problems, and share knowledge to help others.</p><p><strong>Practical Examples:</strong></p><ul><li>The student writes a script to automate data sorting or acts as the class’s ‘IT support’.</li></ul>',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Ensure a binary competency scale exists in Moodle DB and return [scaleid, scaleconfiguration].
     *
     * @return array
     */
    protected static function ensure_binary_scale(): array {
        global $DB, $USER;
        $userid = !empty($USER->id) ? (int)$USER->id : 2;
        $now = time();

        // Check if an existing ACCEES framework already has a valid scaleid.
        $existing_fw = $DB->get_record_sql(
            "SELECT id, scaleid, scaleconfiguration FROM {competency_framework} WHERE idnumber IN ('DIGCOMP_ACCEES_STUDENTS', 'DIGCOMP_ACCEES_FACULTY') AND scaleid > 0 ORDER BY id ASC",
            [],
            IGNORE_MULTIPLE
        );
        if ($existing_fw && $DB->record_exists('scale', ['id' => $existing_fw->scaleid])) {
            $config = !empty($existing_fw->scaleconfiguration)
                ? $existing_fw->scaleconfiguration
                : json_encode([['scaleid' => (string)$existing_fw->scaleid], ['id' => 1, 'scaledefault' => 0, 'proficient' => 0], ['id' => 2, 'scaledefault' => 1, 'proficient' => 1]]);
            return [(int)$existing_fw->scaleid, $config];
        }

        // Search for any existing 2-value scale ('Not yet competent,Competent').
        $scales = $DB->get_records('scale', ['courseid' => 0], 'id ASC');
        foreach ($scales as $sc) {
            $parts = array_map('trim', explode(',', (string)$sc->scale));
            if (count($parts) === 2 && strcasecmp($parts[0], 'Not yet competent') === 0 && strcasecmp($parts[1], 'Competent') === 0) {
                $config = json_encode([['scaleid' => (string)$sc->id], ['id' => 1, 'scaledefault' => 0, 'proficient' => 0], ['id' => 2, 'scaledefault' => 1, 'proficient' => 1]]);
                return [(int)$sc->id, $config];
            }
        }

        // Create standard binary scale.
        $scale = new \stdClass();
        $scale->courseid = 0;
        $scale->userid = $userid;
        $scale->name = 'ACCEES Binary Competency Scale';
        $scale->scale = 'Not yet competent,Competent';
        $scale->description = '<p>Binary Competency-Based Education (CBE) scale: Not yet competent (0), Competent (1).</p>';
        $scale->descriptionformat = FORMAT_HTML;
        $scale->timemodified = $now;
        $scaleid = (int)$DB->insert_record('scale', $scale);
        $config = json_encode([['scaleid' => (string)$scaleid], ['id' => 1, 'scaledefault' => 0, 'proficient' => 0], ['id' => 2, 'scaledefault' => 1, 'proficient' => 1]]);
        return [$scaleid, $config];
    }

    /**
     * Upsert a single competency node in {competency} by frameworkid + idnumber.
     *
     * @param int $frameworkid
     * @param int $parentid
     * @param string $path
     * @param int $sortorder
     * @param string $idnumber
     * @param string $shortname
     * @param string $description
     * @param int $userid
     * @param int $now
     * @return array [int $id, string $action]
     */
    protected static function upsert_competency_node(
        int $frameworkid,
        int $parentid,
        string $path,
        int $sortorder,
        string $idnumber,
        string $shortname,
        string $description,
        int $userid,
        int $now
    ): array {
        global $DB;

        $existing = $DB->get_record('competency', [
            'competencyframeworkid' => $frameworkid,
            'idnumber' => $idnumber,
        ]);

        if ($existing) {
            $changed = (
                (int)$existing->parentid !== $parentid ||
                (string)$existing->path !== $path ||
                (int)$existing->sortorder !== $sortorder ||
                (string)$existing->shortname !== $shortname ||
                (string)$existing->description !== $description ||
                (int)$existing->descriptionformat !== FORMAT_HTML
            );
            if ($changed) {
                $existing->parentid = $parentid;
                $existing->path = $path;
                $existing->sortorder = $sortorder;
                $existing->shortname = $shortname;
                $existing->description = $description;
                $existing->descriptionformat = FORMAT_HTML;
                $existing->timemodified = $now;
                $existing->usermodified = $userid;
                $DB->update_record('competency', $existing);
                return [(int)$existing->id, 'updated'];
            }
            return [(int)$existing->id, 'unchanged'];
        }

        $rec = new \stdClass();
        $rec->shortname = $shortname;
        $rec->description = $description;
        $rec->descriptionformat = FORMAT_HTML;
        $rec->idnumber = $idnumber;
        $rec->competencyframeworkid = $frameworkid;
        $rec->parentid = $parentid;
        $rec->path = $path;
        $rec->sortorder = $sortorder;
        $rec->ruletype = null;
        $rec->ruleoutcome = 0;
        $rec->ruleconfig = null;
        $rec->scaleid = null;
        $rec->scaleconfiguration = null;
        $rec->timecreated = $now;
        $rec->timemodified = $now;
        $rec->usermodified = $userid;
        $newid = (int)$DB->insert_record('competency', $rec);
        return [$newid, 'inserted'];
    }

    /**
     * Synchronize all canonical ACCEES competency frameworks and nodes into Moodle DB.
     *
     * @param callable|null $logger Optional callback function(string $msg)
     * @return array Summary counters
     */
    public static function sync_all(?callable $logger = null): array {
        global $DB, $USER;

        $log = function(string $msg) use ($logger) {
            if ($logger !== null) {
                $logger($msg);
            }
        };

        $stats = [
            'frameworks_synced' => 0,
            'frameworks_created' => 0,
            'frameworks_updated' => 0,
            'frameworks_unchanged' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        if (!$DB) {
            return $stats;
        }

        $userid = !empty($USER->id) ? (int)$USER->id : 2;
        $now = time();
        [$scaleid, $scaleconfig] = self::ensure_binary_scale();
        $syscontext = \context_system::instance();

        foreach (self::get_canonical_frameworks() as $fwdef) {
            $fw_idnum = $fwdef['idnumber'];
            $fw = $DB->get_record('competency_framework', ['idnumber' => $fw_idnum]);
            if (!$fw) {
                $alt_idnum = $fw_idnum . '_V1';
                $fw = $DB->get_record('competency_framework', ['idnumber' => $alt_idnum]);
            }

            if ($fw) {
                $fw_changed = (
                    (string)$fw->idnumber !== $fw_idnum ||
                    (string)$fw->shortname !== $fwdef['shortname'] ||
                    (string)$fw->description !== $fwdef['description'] ||
                    (int)$fw->descriptionformat !== FORMAT_HTML ||
                    (string)$fw->taxonomies !== 'domain,competency,level' ||
                    (int)$fw->visible !== 1 ||
                    empty($fw->scaleid)
                );
                $frameworkid = (int)$fw->id;
                if ($fw_changed) {
                    $fw->idnumber = $fw_idnum;
                    $fw->shortname = $fwdef['shortname'];
                    $fw->description = $fwdef['description'];
                    $fw->descriptionformat = FORMAT_HTML;
                    $fw->taxonomies = 'domain,competency,level';
                    $fw->visible = 1;
                    if (empty($fw->scaleid)) {
                        $fw->scaleid = $scaleid;
                        $fw->scaleconfiguration = $scaleconfig;
                    }
                    $fw->timemodified = $now;
                    $fw->usermodified = $userid;
                    $DB->update_record('competency_framework', $fw);
                    $stats['frameworks_updated']++;
                    $log("[~] Updated Framework: {$fw_idnum} (ID: {$frameworkid})");
                } else {
                    $stats['frameworks_unchanged']++;
                    $log("[=] Unchanged Framework: {$fw_idnum} (ID: {$frameworkid})");
                }
            } else {
                $newfw = new \stdClass();
                $newfw->shortname = $fwdef['shortname'];
                $newfw->contextid = $syscontext->id;
                $newfw->idnumber = $fw_idnum;
                $newfw->description = $fwdef['description'];
                $newfw->descriptionformat = FORMAT_HTML;
                $newfw->scaleid = $scaleid;
                $newfw->scaleconfiguration = $scaleconfig;
                $newfw->visible = 1;
                $newfw->taxonomies = 'domain,competency,level';
                $newfw->timecreated = $now;
                $newfw->timemodified = $now;
                $newfw->usermodified = $userid;
                $frameworkid = (int)$DB->insert_record('competency_framework', $newfw);
                $stats['frameworks_created']++;
                $log("[+] Created Framework: {$fw_idnum} (ID: {$frameworkid})");
            }
            $stats['frameworks_synced']++;

            $dom_sort = 0;
            foreach ($fwdef['domains'] as $domdef) {
                [$dom_id, $dom_act] = self::upsert_competency_node(
                    $frameworkid,
                    0,
                    '/0/',
                    $dom_sort++,
                    $domdef['idnumber'],
                    $domdef['shortname'],
                    $domdef['description'],
                    $userid,
                    $now
                );
                $stats[$dom_act]++;

                $comp_sort = 0;
                foreach ($domdef['competencies'] as $compdef) {
                    $comp_path = '/0/' . $dom_id . '/';
                    [$comp_id, $comp_act] = self::upsert_competency_node(
                        $frameworkid,
                        $dom_id,
                        $comp_path,
                        $comp_sort++,
                        $compdef['idnumber'],
                        $compdef['shortname'],
                        $compdef['description'],
                        $userid,
                        $now
                    );
                    $stats[$comp_act]++;

                    $lvl_sort = 0;
                    foreach ($compdef['levels'] as $lvldef) {
                        $lvl_path = '/0/' . $dom_id . '/' . $comp_id . '/';
                        [, $lvl_act] = self::upsert_competency_node(
                            $frameworkid,
                            $comp_id,
                            $lvl_path,
                            $lvl_sort++,
                            $lvldef['idnumber'],
                            $lvldef['shortname'],
                            $lvldef['description'],
                            $userid,
                            $now
                        );
                        $stats[$lvl_act]++;
                    }
                }
            }
        }

        return $stats;
    }
}
