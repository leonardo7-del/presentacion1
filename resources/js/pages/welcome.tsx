import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login, register } from '@/routes';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Inicio" />

            <div className="flex min-h-screen items-center justify-center bg-background p-6">
                <main className="w-full max-w-xl rounded-xl border border-border bg-card p-8 text-card-foreground">
                    <h1 className="text-2xl font-semibold">Nexus</h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Plataforma de acceso seguro.
                    </p>

                    <div className="mt-6 flex gap-3">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="rounded-md bg-primary px-4 py-2 text-primary-foreground"
                            >
                                Ir al panel
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="rounded-md bg-primary px-4 py-2 text-primary-foreground"
                                >
                                    Iniciar sesión
                                </Link>

                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="rounded-md border border-border px-4 py-2"
                                    >
                                        Registrarse
                                    </Link>
                                )}
                            </>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
