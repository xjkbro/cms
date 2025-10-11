import { dashboard, login, register } from '@/routes';
import  l5Swagger  from '@/routes/l5-swagger';

import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useState, useEffect } from 'react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;
    const props = usePage<SharedData>().props;
    const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });

    console.log(props);

    useEffect(() => {
        const handleMouseMove = (e: MouseEvent) => {
            setMousePosition({ x: e.clientX, y: e.clientY });
        };

        window.addEventListener('mousemove', handleMouseMove);
        return () => window.removeEventListener('mousemove', handleMouseMove);
    }, []);

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet" />
            </Head>
            <div className="relative flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:text-white lg:justify-center lg:p-8 dark:bg-[#0a0a0a] font-quicksand overflow-hidden">
                {/* Cursor Light Effect with Grid */}
                <div
                    className="absolute pointer-events-none z-10"
                    style={{
                        background: `radial-gradient(circle 500px at ${mousePosition.x}px ${mousePosition.y}px,
                            rgba(255, 255, 255, 0.08) 0%,
                            rgba(255, 255, 255, 0.04) 30%,
                            transparent 70%)`,
                        width: '100vw',
                        height: '100vh',
                        top: 0,
                        left: 0,
                        mixBlendMode: 'screen'
                    }}
                />

                {/* Grid Pattern - Only Visible in Light */}
                <div
                    className="absolute pointer-events-none z-20"
                    style={{
                        backgroundImage: `
                            linear-gradient(to right, rgba(27, 27, 24, 0.3) 1px, transparent 1px),
                            linear-gradient(to bottom, rgba(27, 27, 24, 0.3) 1px, transparent 1px)
                        `,
                        backgroundSize: '50px 50px',
                        width: '100vw',
                        height: '100vh',
                        top: 0,
                        left: 0,
                        mask: `radial-gradient(circle 250px at ${mousePosition.x}px ${mousePosition.y}px,
                            rgba(0, 0, 0, 1) 0%,
                            rgba(0, 0, 0, 0.8) 40%,
                            rgba(0, 0, 0, 0.2) 70%,
                            rgba(0, 0, 0, 0.1) 100%)`,
                        WebkitMask: `radial-gradient(circle 250px at ${mousePosition.x}px ${mousePosition.y}px,
                            rgba(0, 0, 0, 1) 0%,
                            rgba(0, 0, 0, 0.8) 40%,
                            rgba(0, 0, 0, 0.2) 70%,
                            rgba(0, 0, 0, 0.1) 100%)`
                    }}
                />

                {/* Alternative approach - visible grid for dark mode */}
                <div
                    className="absolute pointer-events-none z-21 dark:block hidden"
                    style={{
                        backgroundImage: `
                            linear-gradient(to right, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                            linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 1px, transparent 1px)
                        `,
                        backgroundSize: '50px 50px',
                        width: '100vw',
                        height: '100vh',
                        top: 0,
                        left: 0,
                        mask: `radial-gradient(circle 250px at ${mousePosition.x}px ${mousePosition.y}px,
                            rgba(0, 0, 0, 1) 0%,
                            rgba(0, 0, 0, 0.8) 40%,
                            rgba(0, 0, 0, 0.2) 70%,
                            rgba(0, 0, 0, 0.1) 100%)`,
                        WebkitMask: `radial-gradient(circle 250px at ${mousePosition.x}px ${mousePosition.y}px,
                            rgba(0, 0, 0, 1) 0%,
                            rgba(0, 0, 0, 0.8) 40%,
                            rgba(0, 0, 0, 0.2) 70%,
                            rgba(0, 0, 0, 0.1) 100%)`
                    }}
                />

                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl relative z-30">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>
                <main className='flex flex-col w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 relative z-30'>
                    <div className="border-b border-[#e3e3e0] pb-6 dark:border-[#3E3E3A]">
                        <h1 className='text-[6rem] font-bold pointer-events-none'>{props.name}</h1>
                    </div>
                    <div>
                        <p className='mt-6 text-lg'>Welcome to your new rich-headless CMS.</p>
                        <div className='flex justify-center mt-3 '>
                            <Link className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            href={register()}>Get Started</Link>
                        </div>
                    </div>

                    {/* Navigation Links */}
                    <motion.div
                        className="flex gap-12 mt-24 text-sm"
                        initial="hidden"
                        animate="visible"
                        variants={{
                            hidden: { opacity: 0 },
                            visible: {
                                opacity: 1,
                                transition: {
                                    staggerChildren: 0.1,
                                    delayChildren: 0.3
                                }
                            }
                        }}
                    >
                        <motion.a
                            className="hover:text-neutral-400 transition-colors duration-200"
                            href={l5Swagger.default.api().url}
                            variants={{
                                hidden: { opacity: 0, y: 20 },
                                visible: { opacity: 1, y: 0 }
                            }}
                        >
                            API Reference
                        </motion.a>
                        <motion.a
                            className="hover:text-neutral-400 transition-colors duration-200"
                            href={""}
                            variants={{
                                hidden: { opacity: 0, y: 20 },
                                visible: { opacity: 1, y: 0 }
                            }}
                        >
                            Pricing
                        </motion.a>
                        <motion.a
                            className="hover:text-neutral-400 transition-colors duration-200"
                            href={""}
                            variants={{
                                hidden: { opacity: 0, y: 20 },
                                visible: { opacity: 1, y: 0 }
                            }}
                        >
                            Privacy Policy
                        </motion.a>
                        <motion.a
                            className="hover:text-neutral-400 transition-colors duration-200"
                            href={""}
                            variants={{
                                hidden: { opacity: 0, y: 20 },
                                visible: { opacity: 1, y: 0 }
                            }}
                        >
                            Terms of Service
                        </motion.a>
                        <motion.a
                            className="hover:text-neutral-400 transition-colors duration-200"
                            href={""}
                            variants={{
                                hidden: { opacity: 0, y: 20 },
                                visible: { opacity: 1, y: 0 }
                            }}
                        >
                            Contact
                        </motion.a>
                    </motion.div>

                </main>

            </div>
        </>
    );
}
